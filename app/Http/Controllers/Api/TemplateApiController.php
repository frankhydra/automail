<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json(['message' => 'No organization found for this account.'], 404);
        }

        $templates = $organization->templates()
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return TemplateResource::collection($templates)->response();
    }

    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $user->currentOrganization();

        $template = $organization ? $organization->templates()->find($id) : null;

        if (!$template) {
            return response()->json(['message' => 'Template not found.'], 404);
        }

        return (new TemplateResource($template))->response();
    }
}
