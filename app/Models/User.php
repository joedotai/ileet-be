<?php

namespace App\Models;

use App\Traits\Verifiable; // Import the trait
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Verifiable; // Include the trait here

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at', // Ensure this is fillable/cast if needed
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}