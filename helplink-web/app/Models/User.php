<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // ✅ TAMBAH NI
use App\Models\Request;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable; // ✅ TAMBAH HasApiTokens

    protected $fillable = [
        'firebase_uid',
        'name',
        'email',
        'password',
        'address',
        'phone_number',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function offers()
{
    return $this->hasMany(Offer::class, 'user_id');
}

}
