<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Private channel note.{noteId}:
|   - Owner of the note can subscribe
|   - Any user who has a NoteShare record for this note can subscribe
*/

Broadcast::channel('note.{noteId}', function ($user, $noteId) {
    // Owner
    $note = \App\Models\Note::find($noteId);
    if ($note && $note->user_id === $user->id) {
        return ['id' => $user->id, 'name' => $user->display_name];
    }

    // Shared user
    $share = \App\Models\NoteShare::where('note_id', $noteId)
        ->where('recipient_id', $user->id)
        ->where('permission', 'edit')
        ->first();

    if ($share) {
        return ['id' => $user->id, 'name' => $user->display_name];
    }

    return false;
});
