<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteImage extends Model
{
    protected $fillable = [
        'note_id',
        'image_path',
        'original_name',
        'sort_order',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }
}
