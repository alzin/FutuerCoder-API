<?php

namespace App\Http\Controllers;

use App\Models\Cources_time;
use App\Models\FreeLessons;
use App\Models\Cources;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\GuestUsers;
class CourcesTimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($user_id)
    {
        // الحصول على المستخدم
        $user = GuestUsers::find($user_id);

        // التحقق من وجود المستخدم والمنطقة الزمنية
        if (!$user || !$user->timeZone) {
            return response()->json(['message' => 'User or time zone not found'], 404);
        }

        // عرض جميع الكورسات مع استخدام paginate(5)
        $courses = Cources::paginate(5);

        // جلب تواريخ وأوقات الكورسات
        $coursesData = $courses->map(function ($course) use ($user) {
            $courseTimes = Cources_time::where('courseId', $course->id)->get();

            return [
                'courseName' => $course->title,
                'courseId' => $course->id,
                'courseTimes' => $courseTimes->map(function ($time) use ($user) {
                    return [
                        'id' => $time->id,
                        'date' => Carbon::parse($time->SessionTimings, 'UTC')->setTimezone($user->timeZone)->toDateString(),
                        'startTime' => Carbon::parse($time->startTime, 'UTC')->setTimezone($user->timeZone)->toTimeString(),
                        'endTime' => Carbon::parse($time->endTime, 'UTC')->setTimezone($user->timeZone)->toTimeString(),
                    ];
                })
            ];
        });

        return response()->json([
            'courses' => $coursesData,
            'pagination' => [
                'total' => $courses->total(),
                'currentPage' => $courses->currentPage(),
                'lastPage' => $courses->lastPage(),
                'perPage' => $courses->perPage(),
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
        $sessionDateUTC = Carbon::parse($request->input('SessionTimings'), 'UTC');
        $startTime = Carbon::parse($request->input('startTime'), 'Asia/Tokyo');
        $endTime = Carbon::parse($request->input('endTime'), 'Asia/Tokyo');
        $startTimeUTC = $startTime->setTimezone('UTC');
        $endTimeUTC = $endTime->setTimezone('UTC');

        $course_time=Cources_time::create([
            'courseId' => $request->courseId,
            'SessionTimings' => $sessionDateUTC,
            'startTime' => $startTimeUTC,
            'endTime' => $endTimeUTC,
        ]);
        $course=Cources::find($request->courseId);
        return response()->json([
                        'message'=>'course time created ',
                        'course_name'=>$course->title,
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
    public function update(Request $request, $id, $user_id)
{
    // الحصول على المستخدم
    $user = GuestUsers::find($user_id);

    // التحقق من وجود المستخدم والمنطقة الزمنية
    if (!$user || !$user->timeZone) {
        return response()->json(['message' => 'User or time zone not found'], 404);
    }

    // العثور على التوقيت المطلوب تعديله
    $newTime = Cources_time::find($id);

    if (!$newTime) {
        return response()->json(['message' => 'Time not found'], 404);
    }

    // تحويل الأوقات المدخلة من المستخدم إلى UTC قبل حفظها
    $newTime->startTime = Carbon::parse($request->start_time, $user->timeZone)->setTimezone('UTC')->toTimeString();
    $newTime->endTime = Carbon::parse($request->end_time, $user->timeZone)->setTimezone('UTC')->toTimeString();

    // حفظ التعديلات
    $newTime->save();

    return response()->json([
        'message' => 'time updated',
        'data' => $newTime
    ]);
}

    /**
     * الحصول على جميع الأيام (day_of_month) لكورس معين بناءً على course_id.
     *
     * @param int $course_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDaysByCourseId($course_id, $user_id)
{
    // الحصول على المستخدم
    $user = GuestUsers::find($user_id);

    // التحقق من وجود المستخدم والمنطقة الزمنية
    if (!$user || !$user->timeZone) {
        return response()->json(['message' => 'User or time zone not found'], 404);
    }

    // الحصول على الأيام من جدول cources_times بناءً على course_id
    $days = Cources_time::where('courseId', $course_id)
                         ->pluck('SessionTimings');

    // تحويل التواريخ إلى المنطقة الزمنية الخاصة بالمستخدم
    $daysInUserTimeZone = $days->map(function ($day) use ($user) {
        // تحويل التواريخ من UTC إلى المنطقة الزمنية الخاصة بالمستخدم
        return Carbon::parse($day, 'UTC')->setTimezone($user->timeZone)->toDateString();
    });

    // إرجاع التواريخ بعد التحويل
    return response()->json($daysInUserTimeZone);
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
    public function getAvailableTimes($course_id, $sessionTimings, $user_id)
    {
        $user = GuestUsers::find($user_id);
    
        // التحقق من وجود المستخدم والمنطقة الزمنية
        if (!$user || !$user->timeZone) {
            return response()->json(['message' => 'User or time zone not found'], 404);
        }
    
        // الحصول على الوقت الحالي وتحويله إلى UTC
        $nowUTC = Carbon::now('UTC');
    
        // تحويل sessionTimings إلى UTC للمقارنة مع البيانات المخزنة
        // نظرًا لأن التواريخ مخزنة بتوقيت UTC، نقوم بتحليل المدخل باستخدام UTC مباشرة
        $sessionTimingsUTC = Carbon::parse($sessionTimings, 'UTC');
    
        // إضافة شروط للتحقق من أن التاريخ والوقت لم ينقضيا بعد
        $availableTimes = Cources_time::where('courseId', $course_id)
            ->where('SessionTimings', $sessionTimingsUTC->toDateString()) // مقارنة SessionTimings بتوقيت UTC
            ->where('studentsCount', '<', 3)
            ->where(function($query) use ($nowUTC) {
                // شرط لتأكيد أن التاريخ لم ينقض بعد
                $query->where('SessionTimings', '>', $nowUTC->toDateString())
                    // شرط لتأكيد أن الوقت لم ينقض بعد في حال كان التاريخ هو اليوم الحالي
                    ->orWhere(function($query) use ($nowUTC) {
                        $query->where('SessionTimings', $nowUTC->toDateString())
                                ->where('endTime', '>', $nowUTC->toTimeString());
                    });
            })
            ->get(['startTime', 'endTime', 'studentsCount', 'id']);
    
        // تحويل الأوقات المتاحة إلى المنطقة الزمنية الخاصة بالمستخدم
        $availableTimesInUserTimeZone = $availableTimes->map(function ($time) use ($user) {
            return [
                'startTime' => Carbon::parse($time->startTime, 'UTC')->setTimezone($user->timeZone)->toTimeString(),
                'endTime' => Carbon::parse($time->endTime, 'UTC')->setTimezone($user->timeZone)->toTimeString(),
                'studentsCount' => $time->studentsCount,
                'id' => $time->id
            ];
        });
    
        return response()->json(["message" => "successful", "data" => $availableTimesInUserTimeZone]);
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
