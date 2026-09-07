<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\User;
use App\Services\TemplateRendererService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TemplateController extends Controller
{
    protected TemplateRendererService $rendererService;

    public function __construct(TemplateRendererService $rendererService)
    {
        $this->rendererService = $rendererService;
    }

    /**
     * Display a listing of templates for current organization.
     */
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $templates = $organization
            ? $organization->templates()->latest()->get()
            : collect();

        return view('templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(): View
    {
        return view('templates.create');
    }

    /**
     * Store a newly created template in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        if (!$organization) {
            return redirect()->back()->withErrors(['error' => 'No active organization found.']);
        }

        $organization->templates()->create([
            'name' => trim($request->name),
            'subject' => trim($request->subject),
            'content' => $request->content,
        ]);

        return redirect()->route('templates.index')
            ->with('status', 'Email template created successfully!');
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(int $id): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $template = $organization
            ? $organization->templates()->find($id)
            : null;

        if (!$template) {
            return redirect()->route('templates.index')->withErrors(['error' => 'Template not found.']);
        }

        // Preview with sample data
        $sampleData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ];

        $renderedSubject = $this->rendererService->render($template->subject, $sampleData);
        $renderedBody = $this->rendererService->render($template->content, $sampleData);

        return view('templates.edit', compact('template', 'renderedSubject', 'renderedBody'));
    }

    /**
     * Update the specified template in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $template = $organization
            ? $organization->templates()->find($id)
            : null;

        if (!$template) {
            return redirect()->route('templates.index')->withErrors(['error' => 'Template not found.']);
        }

        $template->update([
            'name' => trim($request->name),
            'subject' => trim($request->subject),
            'content' => $request->content,
        ]);

        return redirect()->route('templates.index')
            ->with('status', 'Email template updated successfully!');
    }

    /**
     * Remove the specified template from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        if ($organization) {
            $organization->templates()->where('id', $id)->delete();
        }

        return redirect()->route('templates.index')
            ->with('status', 'Template deleted successfully.');
    }
}