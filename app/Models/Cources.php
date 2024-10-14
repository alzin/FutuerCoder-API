<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Cources extends Model
{
    use HasFactory;
    protected $fillable=
    [
    'title',
    'teacher',
    'description',
    'imagePath',
    'price',
    'course_outline',
    'duration_in_session',
    'course_start_date',
    'min_age',
    'max_age'
    ];

    public function courseTimes()
    {
        return $this->hasMany(Cources_time::class);
    }
    public function cources_times()
    {
        return $this->hasMany(Cources_time::class, 'courseId');
    }
}
