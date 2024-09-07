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
    ];

}
