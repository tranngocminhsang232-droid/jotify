<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferenceController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $preferences = $user->preferences()->firstOrCreate([], [
            'font_size' => 'medium',
            'note_color' => 'none',
            'theme' => 'light',
            'view_mode' => 'grid',
        ]);
        return view('preferences.edit', compact('preferences'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'font_size'  => 'required|in:small,medium,large,x-large',
            'note_color' => 'required|string|max:7',
        ]);

        $user = Auth::user();
        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            $request->only(['font_size', 'note_color'])
        );

        return back()->with('success', 'Preferences updated successfully.');
    }

    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:light,dark',
        ]);

        $user = Auth::user();
        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            ['theme' => $request->theme]
        );

        return response()->json(['success' => true]);
    }

    public function updateViewMode(Request $request)
    {
        $request->validate([
            'view_mode' => 'required|in:grid,list',
        ]);

        $user = Auth::user();
        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            ['view_mode' => $request->view_mode]
        );

        return response()->json(['success' => true]);
    }
}
