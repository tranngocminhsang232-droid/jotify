<?php

namespace App\Http\Controllers;

use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabelController extends Controller
{
    public function index()
    {
        $labels = Auth::user()->labels()->withCount('notes')->orderBy('name')->get();
        return response()->json($labels);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:7',
        ]);

        $user = Auth::user();

        // Check uniqueness for this user
        if ($user->labels()->where('name', $request->name)->exists()) {
            return response()->json(['error' => 'Label already exists.'], 422);
        }

        $label = $user->labels()->create([
            'name' => $request->name,
            'color' => $request->color ?? '#6366f1',
        ]);

        return response()->json($label, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:7',
        ]);

        $user = Auth::user();
        $label = $user->labels()->findOrFail($id);

        // Check uniqueness for this user (excluding current label)
        if ($user->labels()->where('name', $request->name)->where('id', '!=', $id)->exists()) {
            return response()->json(['error' => 'Label name already exists.'], 422);
        }

        $label->update($request->only(['name', 'color']));

        return response()->json($label);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $label = $user->labels()->findOrFail($id);

        // Detach from notes (don't delete notes)
        $label->notes()->detach();
        $label->delete();

        return response()->json(['success' => true]);
    }

    public function manage()
    {
        $labels = Auth::user()->labels()->withCount('notes')->orderBy('name')->get();
        return view('labels.manage', compact('labels'));
    }
}
