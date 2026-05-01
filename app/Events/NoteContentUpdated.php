<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteContentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $noteId;
    public string $title;
    public string $content;
    public string $updatedBy;
    public string $updatedAt;

    public function __construct(int $noteId, string $title, string $content, string $updatedBy, string $updatedAt)
    {
        $this->noteId    = $noteId;
        $this->title     = $title;
        $this->content   = $content;
        $this->updatedBy = $updatedBy;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Broadcast on private channel note.{noteId}
     * Both owner and all shared users subscribe to this channel.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('note.' . $this->noteId);
    }

    /**
     * Event name the frontend listens for:
     *   Echo.private('note.X').listen('.NoteContentUpdated', cb)
     */
    public function broadcastAs(): string
    {
        return 'NoteContentUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'note_id'    => $this->noteId,
            'title'      => $this->title,
            'content'    => $this->content,
            'updated_by' => $this->updatedBy,
            'updated_at' => $this->updatedAt,
        ];
    }
}
