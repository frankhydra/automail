<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Integration;
use App\Models\SuppressionList;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Public endpoint other tools call to add a contact (a website form, Zapier, Make,
 * a checkout...). Unauthenticated by nature, so it is protected by:
 *  - a long random secret in the URL, stored only as a hash and revocable,
 *  - rate limiting (see the route),
 *  - the same rules as adding a contact by hand: plan limit, duplicates, and the
 *    suppression list (nobody who unsubscribed or bounced is silently re-added).
 *
 * Whoever calls this is stating that the person agreed to receive your emails.
 */
class ContactIntakeController extends Controller
{
    public function store(Request $request, string $token, PlanLimitService $planLimits): JsonResponse
    {
        $integration = Integration::where('type', 'webhook')
            ->where('secret_hash', hash('sha256', $token))
            ->where('enabled', true)
            ->first();

        // Same answer for "wrong token" and "switched off": nothing to learn from it.
        if (!$integration) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $tags = $request->input('tags');
        $request->merge(['tags' => is_array($tags) ? implode(',', array_map('strval', array_filter($tags, 'is_scalar'))) : $tags]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid data.', 'errors' => $validator->errors()], 422);
        }

        $organization = $integration->organization;
        $email = strtolower(trim((string) $request->input('email')));

        $integration->increment('uses');
        $integration->forceFill(['last_used_at' => now()])->save();

        // Calling twice with the same address (Zapier retries, double submits) is harmless.
        if ($organization->contacts()->where('email', $email)->exists()) {
            return response()->json(['status' => 'exists']);
        }

        if (SuppressionList::where('organization_id', $organization->id)->where('email', $email)->exists()) {
            return response()->json(['status' => 'skipped', 'reason' => 'suppressed']);
        }

        if (!$planLimits->canAddContacts($organization)) {
            return response()->json(['message' => 'The contact limit of the plan has been reached.'], 422);
        }

        $defaultTags = Contact::splitTags((string) $integration->setting('default_tags', ''));
        $sentTags = Contact::splitTags((string) $request->input('tags', ''));
        $tags = array_values(array_unique(array_merge($defaultTags, $sentTags)));

        $contact = $organization->contacts()->create([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $email,
            'status' => 'subscribed',
            'tags' => $tags === [] ? null : mb_substr(implode(', ', $tags), 0, 255),
        ]);

        return response()->json(['status' => 'created', 'id' => $contact->id], 201);
    }
}
