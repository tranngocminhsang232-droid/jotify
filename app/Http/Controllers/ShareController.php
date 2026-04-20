<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteImage;
use App\Models\NoteShare;
use App\Models\User;
use App\Models\Notification;
use App\Events\NoteContentUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ShareController extends Controller
{
    // Show shared with me notes
    public function sharedWithMe()
    {
        $user = Auth::user();
        $sharedNotes = NoteShare::where('recipient_id', $user->id)
            ->with(['note', 'owner'])
            ->orderBy('shared_at', 'desc')
            ->get();

        $preferences = $user->preferences()->firstOrCreate([], [
            'font_size' => 'medium', 'note_color' => '#ffffff',
            'theme' => 'light', 'view_mode' => 'grid',
        ]);

        return view('notes.shared', compact('sharedNotes', 'preferences'));
    }

    // Share a note
    public function share(Request $request, $noteId)
    {
        $request->validate([
            'email' => 'required|email',
            'permission' => 'required|in:read,edit',
        ]);

        $user = Auth::user();
        $note = $user->notes()->findOrFail($noteId);

        // Can't share with self
        if ($request->email === $user->email) {
            return response()->json(['error' => 'You cannot share a note with yourself.'], 422);
        }

        // Find recipient
        $recipient = User::where('email', $request->email)->first();
        if (!$recipient) {
            return response()->json(['error' => 'No registered user found with this email address.'], 422);
        }

        // Check if already shared
        $existing = NoteShare::where('note_id', $note->id)
            ->where('recipient_id', $recipient->id)
            ->first();

        if ($existing) {
            $existing->update([
                'permission' => $request->permission,
            ]);
            $share = $existing;
        } else {
            $share = NoteShare::create([
                'note_id'      => $note->id,
                'owner_id'     => $user->id,
                'recipient_id' => $recipient->id,
                'permission'   => $request->permission,
                'shared_at'    => now(),
            ]);

            // Create notification only for NEW shares
            Notification::create([
                'user_id' => $recipient->id,
                'type'    => 'note_shared',
                'message' => $user->display_name . ' shared a note "' . ($note->title ?: 'Untitled') . '" with you.',
                'data'    => json_encode([
                    'note_id'    => $note->id,
                    'share_id'   => $share->id,
                    'permission' => $request->permission,
                ]),
            ]);
        }

        // Try to send email notification
        try {
            Mail::send('emails.note-shared', [
                'sharer' => $user,
                'recipient' => $recipient,
                'note' => $note,
                'permission' => $request->permission,
            ], function ($message) use ($recipient) {
                $message->to($recipient->email)
                        ->subject('A note has been shared with you on NoteKeeper');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send share notification email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'share' => $share->load('recipient'),
        ]);
    }

    // Get share details for a note
    public function getShares($noteId)
    {
        $user = Auth::user();
        $note = $user->notes()->findOrFail($noteId);

        $shares = $note->shares()->with('recipient')->get();

        return response()->json($shares);
    }

    // Update share permission
    public function updatePermission(Request $request, $shareId)
    {
        $request->validate([
            'permission' => 'required|in:read,edit',
        ]);

        $user = Auth::user();
        $share = NoteShare::where('owner_id', $user->id)->findOrFail($shareId);

        $share->update(['permission' => $request->permission]);

        return response()->json(['success' => true, 'share' => $share]);
    }

    // Revoke share
    public function revoke($shareId)
    {
        $user = Auth::user();
        $share = NoteShare::where('owner_id', $user->id)->findOrFail($shareId);

        $share->delete();

        return response()->json(['success' => true]);
    }

    // View a shared note (as recipient)
    public function viewSharedNote($shareId)
    {
        $user = Auth::user();
        $share = NoteShare::where('recipient_id', $user->id)
            ->with(['note.images', 'note.labels', 'owner'])
            ->findOrFail($shareId);

        $preferences = $user->preferences()->firstOrCreate([], [
            'font_size' => 'medium', 'note_color' => '#ffffff',
            'theme' => 'light', 'view_mode' => 'grid',
        ]);

        return view('notes.shared-editor', compact('share', 'preferences'));
    }

    // Auto-save for shared note with edit permission
    public function autoSaveShared(Request $request, $shareId)
    {
        $request->validate([
            'title'   => 'nullable|string|max:255',
            'content' => 'nullable|string',
        ]);

        $user  = Auth::user();
        $share = NoteShare::where('recipient_id', $user->id)
            ->where('permission', 'edit')
            ->with('note')
            ->findOrFail($shareId);

        if (!$share->note) {
            return response()->json(['error' => 'Note not found.'], 404);
        }

        $note = $share->note;
        $note->update([
            'title'   => $request->input('title', ''),
            'content' => $request->input('content', ''),
        ]);

        // Broadcast to owner (and other collaborators) via Pusher
        try {
            broadcast(new NoteContentUpdated(
                $note->id,
                $note->title ?? '',
                $note->content ?? '',
                $user->display_name,
                $note->fresh()->updated_at->format('M d, Y h:i A')
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning('Broadcast failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'    => true,
            'updated_at' => $note->fresh()->updated_at->format('M d, Y h:i A'),
        ]);
    }

    // ─── Image upload for recipient with edit permission ──────────────────────
    public function uploadSharedImage(Request $request, $shareId)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $user  = Auth::user();
        $share = NoteShare::where('recipient_id', $user->id)
            ->where('permission', 'edit')
            ->with('note')
            ->findOrFail($shareId);

        $note = $share->note;
        $path = $request->file('image')->store('note-images/' . $note->id, 'public');
        $maxSort = $note->images()->max('sort_order') ?? 0;

        $image = NoteImage::create([
            'note_id'       => $note->id,
            'image_path'    => $path,
            'original_name' => $request->file('image')->getClientOriginalName(),
            'sort_order'    => $maxSort + 1,
        ]);

        return response()->json([
            'success' => true,
            'image'   => [
                'id'            => $image->id,
                'url'           => asset('storage/' . $image->image_path),
                'original_name' => $image->original_name,
            ],
        ]);
    }

    // ─── Image delete for recipient with edit permission ───────────────────────
    public function deleteSharedImage($shareId, $imageId)
    {
        $user  = Auth::user();
        $share = NoteShare::where('recipient_id', $user->id)
            ->where('permission', 'edit')
            ->with('note')
            ->findOrFail($shareId);

        $image = $share->note->images()->findOrFail($imageId);
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json(['success' => true]);
    }

    // Get latest content for real-time collaboration polling
    public function getLatestShared($shareId)
    {
        $user = Auth::user();

        // Allow both the recipient AND the owner to poll for the latest content
        $share = NoteShare::where(function ($q) use ($user) {
                $q->where('recipient_id', $user->id)
                  ->orWhere('owner_id', $user->id);
            })
            ->with('note')
            ->findOrFail($shareId);

        return response()->json([
            'title'      => $share->note->title,
            'content'    => $share->note->content,
            'updated_at' => $share->note->updated_at->toISOString(),
        ]);
    }

    // Notifications
    public function notifications()
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($notifications);
    }

    public function markNotificationRead($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function unreadCount()
    {
        $count = Auth::user()->notifications()->where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }
}
