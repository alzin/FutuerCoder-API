<?php

namespace App\Http\Controllers;

use App\Models\Cources_time;
use App\Models\FreeLessons;
use App\Models\Cources;
use Illuminate\Http\Request;
use Carbon\Carbon;
class CourcesTimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // عرض جميع الكورسات مع استخدام paginate(5)
        $courses = Cources::paginate(5);

        // جلب تواريخ وأوقات الكورسات
        $coursesData = $courses->map(function ($course) {
            $courseTimes = Cources_time::where('courseId', $course->id)->get();

            return [
                'course_name' => $course->title,
                'course_times' => $courseTimes->map(function ($time) {
                    return [
                        'date' => $time->SessionTimings,
                        'StartTime' => $time->startTime,
                        'endTime' => $time->endTime,
                    ];
                })
            ];
        });

        return response()->json([
            'courses' => $coursesData,
            'pagination' => [
                'total' => $courses->total(),
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
            ]
        ]);
    }

       
    

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $request->validate([
            'courseId' => 'required',
            'SessionTimings'=>'required',
            'startTime' => 'required',
            'endTime' => 'required',
        ]);
        $course_time=Cources_time::create($request->only(['courseId','SessionTimings', 'startTime','endTime']));
        $course=Cources::find($request->courseId);
        return response()->json([
                        'message'=>'course time created ',
                        'course_name:'=>$course->title,
                        'data'=>$course_time

        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Cources_time $cources_time)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cources_time $cources_time)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        $newTime=Cources_time::find($id);
        $newTime->start_time=$request->start_time;
        $newTime->end_time=$request->end_time;
        $newTime->save();
        return response()->json([
                                'message'=>'time updated',
                                'data'=>$newTime
                                ]);
    }
    /**
     * الحصول على جميع الأيام (day_of_month) لكورس معين بناءً على course_id.
     *
     * @param int $course_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDaysByCourseId($course_id)
    {
        // الحصول على الأيام من جدول cources_times بناءً على course_id
        $days = Cources_time::where('courseId', $course_id)
                          ->pluck('SessionTimings');
        return response()->json($days);
    }
    /**
     * الحصول على جميع الأوقات (start_time و end_time) لكورس معين في يوم محدد.
     *
     * @param int $course_id
     * @param  $sessionTimings
     * @return \Illuminate\Http\JsonResponse
     */
    /*
    public function getAvailableTimes($course_id, $sessionTimings)
    {
        $availableTimes = Cources_time::where('courseId', $course_id)
            ->where('SessionTimings', $sessionTimings)
            ->where('studentsCount', '<', 3)
            ->get(['startTime', 'endTime', 'studentsCount','id']);
            
        return response()->json(["message"=>"successful","data"=>$availableTimes]);
    }*/
    public function getAvailableTimes($course_id, $sessionTimings)
    {
        // الحصول على الوقت الحالي
        $now = Carbon::now();
    
        // إضافة شروط للتحقق من أن التاريخ والوقت لم ينقضيا بعد
        $availableTimes = Cources_time::where('courseId', $course_id)
            ->where('SessionTimings', $sessionTimings)
            ->where('studentsCount', '<', 3)
            ->where(function($query) use ($now) {
                // شرط لتأكيد أن التاريخ لم ينقض بعد
                $query->where('SessionTimings', '>', $now->toDateString())
                      // شرط لتأكيد أن الوقت لم ينقض بعد في حال كان التاريخ هو اليوم الحالي
                      ->orWhere(function($query) use ($now) {
                          $query->where('SessionTimings', $now->toDateString())
                                ->where('endTime', '>', $now->toTimeString());
                      });
            })
            ->get(['startTime', 'endTime', 'studentsCount', 'id']);
            
        return response()->json(["message" => "successful", "data" => $availableTimes]);
    }


    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Request $request)
    {
        $timeToDelete=Cources_time::find($request->id);
        if(!$timeToDelete){
            return response()->json(['message'=>'date not found']);
        }
        $timeToDelete->forceDelete();
        return response()->json(['message'=>'deleted done']);
    }
}
