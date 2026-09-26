<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactListResource;
use App\Http\Resources\ContactResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json(['message' => 'No organization found for this account.'], 404);
        }

        $contacts = $organization->contacts()
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return ContactResource::collection($contacts)->response();
    }

    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $user->currentOrganization();

        $contact = $organization ? $organization->contacts()->find($id) : null;

        if (!$contact) {
            return response()->json(['message' => 'Contact not found.'], 404);
        }

        return (new ContactResource($contact))->response();
    }

    public function lists(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json(['message' => 'No organization found for this account.'], 404);
        }

        $lists = $organization->contactLists()->withCount('contacts')->get();

        return ContactListResource::collection($lists)->response();
    }
}
