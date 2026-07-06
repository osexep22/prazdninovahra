<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'display_name',
        'username',
        'role',
        'status',
        'is_test',
        'registration_source',
        'friend_code',
        'ant_avatar_config',
        'colony_level',
        'resources',
        'prestige',
        'last_customization_change_at',
        'password',
        'admin_contact_password',
        'admin_contact_code_hash',
        'admin_contact_code_encrypted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'admin_contact_password',
        'admin_contact_code_hash',
        'admin_contact_code_encrypted',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_customization_change_at' => 'datetime',
            'is_test' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
