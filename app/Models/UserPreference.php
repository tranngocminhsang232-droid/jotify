<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'font_size',
        'note_color',
        'theme',
        'view_mode',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
