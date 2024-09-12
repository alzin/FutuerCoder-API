<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestUsers extends Model
{
    use HasFactory;
    protected $fillable=[
        'courseId',
        'firstName',
        'lastName',
        'age',
        'email',
        'verification_token',
        'email_verified_at',
        'email_verified',
        'timeZone'
    ];

}
