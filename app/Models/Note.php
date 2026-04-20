<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'is_pinned',
        'pinned_at',
        'has_password',
        'note_password',
        'note_color',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'has_password' => 'boolean',
            'pinned_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function labels()
    {
        return $this->belongsToMany(Label::class, 'label_note')->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(NoteImage::class)->orderBy('sort_order');
    }

    public function shares()
    {
        return $this->hasMany(NoteShare::class);
    }

    public function isShared()
    {
        return $this->shares()->exists();
    }

    public function isSharedWith($userId)
    {
        return $this->shares()->where('recipient_id', $userId)->exists();
    }

    public function getSharePermission($userId)
    {
        $share = $this->shares()->where('recipient_id', $userId)->first();
        return $share ? $share->permission : null;
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true)->orderBy('pinned_at', 'desc');
    }

    public function scopeUnpinned($query)
    {
        return $query->where('is_pinned', false)->latest('updated_at');
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('content', 'like', "%{$keyword}%");
        });
    }

    public function scopeForLabel($query, $labelIds)
    {
        if (is_array($labelIds) && count($labelIds) > 0) {
            return $query->whereHas('labels', function ($q) use ($labelIds) {
                $q->whereIn('labels.id', $labelIds);
            });
        }
        return $query;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('is_pinned', 'desc')
                     ->orderBy('pinned_at', 'desc')
                     ->orderBy('updated_at', 'desc');
    }
}
