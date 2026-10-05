<?php

namespace App\Http\Controllers;

use App\Support\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Colour themes are a personal display preference, so every signed-in member can change
 * their own - no team-role check. The choice is stored on the user, not the organization.
 */
class ThemeController extends Controller
{
    public function index(): View
    {
        return view('themes.index', [
            'palettes' => Theme::all(),
            'current' => Theme::current(Auth::user()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'palette' => ['required', 'string', Rule::in(array_keys(Theme::all()))],
        ]);

        // forceFill: keeps this feature self-contained (no change to User::$fillable).
        $request->user()->forceFill(['palette' => $data['palette']])->save();

        $name = Theme::all()[$data['palette']]['name'];

        return redirect()->route('themes.index')->with('status', "Theme changed to {$name}.");
    }
}
