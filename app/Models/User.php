<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'display_name',
        'avatar',
        'activation_token',
        'is_activated',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'activation_token',
    ];

    // Include avatar_url accessor in all JSON serializations
    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_activated' => 'boolean',
        ];
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function labels()
    {
        return $this->hasMany(Label::class);
    }

    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function sharedNotes()
    {
        return $this->hasMany(NoteShare::class, 'owner_id');
    }

    public function receivedNotes()
    {
        return $this->hasMany(NoteShare::class, 'recipient_id');
    }

    public function appNotifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function getPreferencesAttribute()
    {
        return $this->preferences()->firstOrCreate([], [
            'font_size' => 'medium',
            'note_color' => '#ffffff',
            'theme' => 'light',
            'view_mode' => 'grid',
        ]);
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->display_name) . '&background=6366f1&color=fff&size=128';
    }
}
