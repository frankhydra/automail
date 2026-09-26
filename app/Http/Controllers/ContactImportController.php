<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ContactImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactImportController extends Controller
{
    protected ContactImportService $importService;

    public function __construct(ContactImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display the contact import form and existing lists.
     */
    public function show(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $lists = $organization
            ? $organization->contactLists()->orderBy('name')->get()
            : collect();

        $recentContacts = $organization
            ? $organization->contacts()->latest()->take(10)->get()
            : collect();

        return view('contacts.import', compact('lists', 'recentContacts'));
    }

    /**
     * Handle the CSV upload and import process.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        if (!$organization) {
            return redirect()->back()->withErrors(['error' => 'No active organization found for your account.']);
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
            // The chosen list must belong to the current organization (tenant isolation).
            'contact_list_id' => [
                'nullable',
                Rule::exists('contact_lists', 'id')->where('organization_id', $organization->id),
            ],
            'new_list_name' => 'nullable|string|max:255',
        ]);

        // If user entered a new list name, create it
        $targetListId = $request->contact_list_id;
        if ($request->filled('new_list_name')) {
            $newList = $organization->contactLists()->create([
                'name' => trim($request->new_list_name),
            ]);
            $targetListId = $newList->id;
        }

        $result = $this->importService->import(
            $request->file('csv_file'),
            $organization,
            $targetListId ? (int) $targetListId : null
        );

        $message = "Import complete: {$result['imported']} added, {$result['updated']} updated, {$result['skipped']} skipped.";

        return redirect()->route('contacts.import.show')->with([
            'status' => $message,
            'import_errors' => $result['errors'],
        ]);
    }
}
