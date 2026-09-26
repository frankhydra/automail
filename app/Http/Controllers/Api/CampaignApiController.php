<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json(['message' => 'No organization found for this account.'], 404);
        }

        $campaigns = $organization->campaigns()
            ->with('sendingIdentity')
            ->withCount('recipients')
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return CampaignResource::collection($campaigns)->response();
    }

    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $user->currentOrganization();

        $campaign = $organization
            ? $organization->campaigns()->with(['sendingIdentity', 'recipients'])->find($id)
            : null;

        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found.'], 404);
        }

        return (new CampaignResource($campaign))->response();
    }
}
