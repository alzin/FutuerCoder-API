<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cources_time extends Model
{
    use HasFactory;
    protected $fillable=[
        'courseId',
        'SessionTimings',
        'startTime',
        'endTime',
    ];

    public function freeLessons()
    {
        return $this->hasMany(FreeLessons::class, 'courseId', 'courseId')
                    ->whereColumn('startTime', 'startTime')
                    ->whereColumn('endTime', 'endTime');
    }
    public function course()
{
    return $this->belongsTo(Cources::class, 'courseId');
}


}
