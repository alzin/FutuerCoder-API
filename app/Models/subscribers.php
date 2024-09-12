<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class subscribers extends Model
{
    use HasFactory;
    protected $fillable=['email','email_verified_at','verification_token','email_verified'];
}
