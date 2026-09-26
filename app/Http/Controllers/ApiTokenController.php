<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    use EnsuresTeamPermission;

    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);

        return view('api-tokens.index', [
            'tokens' => $user->tokens()->orderByDesc('created_at')->get(),
            'plainTextToken' => session('plain_text_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = $user->createToken(trim($data['name']));

        // Sanctum only ever reveals the plain-text token at creation time; it can't
        // be retrieved again afterwards, so it's flashed once for this one redirect.
        return redirect()->route('api-tokens.index')
            ->with('plain_text_token', $token->plainTextToken)
            ->with('status', 'Token created - copy it now, it will not be shown again.');
    }

    public function destroy(int $tokenId): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);

        $user->tokens()->where('id', $tokenId)->delete();

        return redirect()->route('api-tokens.index')->with('status', 'Token revoked.');
    }
}
