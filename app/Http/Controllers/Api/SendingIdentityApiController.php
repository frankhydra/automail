<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SendingIdentityResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SendingIdentityApiController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json(['message' => 'No organization found for this account.'], 404);
        }

        $identities = $organization->sendingIdentities()->orderBy('created_at', 'desc')->get();

        return SendingIdentityResource::collection($identities)->response();
    }
}
