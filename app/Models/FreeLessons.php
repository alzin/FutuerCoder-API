<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreeLessons extends Model
{
    use HasFactory;
    protected $fillable =[
        'userId',
        'courseId',
        'sessionTime',
        'meetUrl',
        'eventId',
    ];
   
}
