<?php

namespace App\Http\Controllers;

use App\Models\Cources_time;
use App\Models\FreeLessons;
use App\Models\Cources;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\GuestUsers;
use Illuminate\Support\Facades\Log;
class CourcesTimeController extends Controller
{
    /**
     * Display a listing of the resource.
     * this function will display the time of course based on the user timezone
     */
    public function index($user_id)
    {
        $user = GuestUsers::find($user_id);

        if (!$user || !$user->timeZone) {
            return response()->json(['message' => 'User or time zone not found'], 404);
        }
        $courses = Cources::paginate(5);
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
        'SessionTimings' => 'required|date',
        'startTime' => 'required|date_format:H:i:s',
        'endTime' => 'required|date_format:H:i:s',
    ]);

    // Retrieve and combine date and time inputs
    $sessionDate = $request->input('SessionTimings'); // Format: Y-m-d
    $startTime = $request->input('startTime'); // Format: H:i:s
    $endTime = $request->input('endTime'); // Format: H:i:s

    $startTimeFull = $sessionDate . ' ' . $startTime;
    $endTimeFull = $sessionDate . ' ' . $endTime;

    // Convert startTime and endTime to Carbon objects in Asia/Tokyo timezone and then to UTC
    $startTimeTokyo = Carbon::createFromFormat('Y-m-d H:i:s', $startTimeFull, 'Asia/Tokyo');
    $endTimeTokyo = Carbon::createFromFormat('Y-m-d H:i:s', $endTimeFull, 'Asia/Tokyo');

    $startTimeUTC = $startTimeTokyo->setTimezone('UTC');
    $endTimeUTC = $endTimeTokyo->setTimezone('UTC');

    // Check if the start and end times are on the same day in UTC
    if ($startTimeUTC->toDateString() !== $endTimeUTC->toDateString()) {
        // If they are on different days, you might want to handle this case differently
        // For example, you can choose to throw an exception or set a specific behavior
        // Here, we will just set the session date to the original SessionTimings
        $sessionDateUTC = $sessionDate; // Keep the original date
    } else {
        // If they are on the same day, keep the original SessionTimings in UTC
        $sessionDateUTC = $startTimeUTC->toDateString();
    }

    // Save into database with UTC time
    $course_time = Cources_time::create([
        'courseId' => $request->courseId,
        'SessionTimings' => $sessionDateUTC, // Save SessionTimings in UTC
        'startTime' => $startTimeUTC->toTimeString(), // Save startTime in UTC
        'endTime' => $endTimeUTC->toTimeString(), // Save endTime in UTC
    ]);

    $course = Cources::find($request->courseId);

    return response()->json([
        'message' => 'course time created',
        'course_name' => $course->title,
        'data' => $course_time
    ]);
}


    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id, $user_id)
    {
        $user = GuestUsers::find($user_id);
        if (!$user || !$user->timeZone) {
            return response()->json(['message' => 'User or time zone not found'], 404);
        }
        $newTime = Cources_time::find($id);

        if (!$newTime) {
            return response()->json(['message' => 'Time not found'], 404);
        }

       // convert the time format into UTC before saving it in database
        $newTime->startTime = Carbon::parse($request->start_time, $user->timeZone)->setTimezone('UTC')->toTimeString();
        $newTime->endTime = Carbon::parse($request->end_time, $user->timeZone)->setTimezone('UTC')->toTimeString();

        $newTime->save();

        return response()->json([
            'message' => 'time updated',
            'data' => $newTime
        ]);
    }

    /**
     * get all dates for course based in user timezone
     *
     * @param int $course_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDaysByCourseId($course_id, $user_id)
{
    $user = GuestUsers::find($user_id);

    if (!$user || !$user->timeZone) {
        return response()->json(['message' => 'User or time zone not found'], 404);
    }

    $days = Cources_time::where('courseId', $course_id)
                         ->pluck('SessionTimings');

    $daysInUserTimeZone = $days->map(function ($day) use ($user) {
        return Carbon::parse($day, 'UTC')->setTimezone($user->timeZone)->toDateString();
    });

   
    return response()->json($daysInUserTimeZone);
}

    /**
     *git all times for a course date (startTime,endTime) based on user timezone
     *
     * @param int $course_id
     * @param  $sessionTimings
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableTimes($course_id, $sessionTimings, $user_id)
    {
        $user = GuestUsers::find($user_id);
    
      
        if (!$user || !$user->timeZone) {
            return response()->json(['message' => 'User or time zone not found'], 404);
        }
    
        //get the time in this moment and convert it to UTC
        $nowUTC = Carbon::now('UTC');
        $sessionTimingsUTC = Carbon::parse($sessionTimings, 'UTC');
        //add a condition to sure that the time not gone yet
        $availableTimes = Cources_time::where('courseId', $course_id)
            ->where('SessionTimings', $sessionTimingsUTC->toDateString()) 
            ->where('studentsCount', '<', 3)
            ->where(function($query) use ($nowUTC) {
                //add a condition to sure that the date not gone yet
                $query->where('SessionTimings', '>', $nowUTC->toDateString())
                    ->orWhere(function($query) use ($nowUTC) {
                        $query->where('SessionTimings', $nowUTC->toDateString())
                                ->where('endTime', '>', $nowUTC->toTimeString());
                    });
            })
            ->get(['startTime', 'endTime', 'studentsCount', 'id']);
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
    
        public function getAvailableTimeZone(Request $request)
{
    if (!$request->timezone) {
        return response()->json(['message' => 'Timezone is required'], 400);
    }

    $timezone = $request->timezone;

    // الحصول على الوقت الحالي في المنطقة الزمنية المطلوبة
    $nowInRequestedTimeZone = Carbon::now($timezone);
    $nowUTC = $nowInRequestedTimeZone->copy()->setTimezone('UTC');

    // استعلام لجلب الأوقات المتاحة
    $availableTimes = Cources_time::where('courseId', $request->course_id)
        ->where('studentsCount', '<', 3)
        ->where(function ($query) use ($nowUTC) {
            $query->where('SessionTimings', '>', $nowUTC->toDateString())
                ->orWhere(function ($query) use ($nowUTC) {
                    $query->where('SessionTimings', $nowUTC->toDateString())
                        ->where('endTime', '>', $nowUTC->toTimeString());
                });
        })
        ->get(['SessionTimings', 'startTime', 'endTime', 'studentsCount', 'id']);

    // تحويل الأوقات المتاحة إلى المنطقة الزمنية المطلوبة
    $availableTimesInRequestedTimeZone = $availableTimes->map(function ($time) use ($timezone) {
        // دمج SessionTimings مع startTime للتاريخ والوقت الكامل
        $startDateTimeUTC = Carbon::parse($time->SessionTimings . ' ' . $time->startTime, 'UTC');
        $endDateTimeUTC = Carbon::parse($time->SessionTimings . ' ' . $time->endTime, 'UTC');

        // تحويل التوقيتات إلى المنطقة الزمنية المطلوبة
        $startDateTimeInRequestedTimezone = $startDateTimeUTC->setTimezone($timezone);
        $endDateTimeInRequestedTimezone = $endDateTimeUTC->setTimezone($timezone);

        return [
            'SessionTimings' => $startDateTimeInRequestedTimezone->toDateString(), // التاريخ
            'startTime' => $startDateTimeInRequestedTimezone->toTimeString(), // وقت البدء
            'endTime' => $endDateTimeInRequestedTimezone->toTimeString(), // وقت النهاية
            'studentsCount' => $time->studentsCount,
            'id' => $time->id
        ];
    });

    return response()->json(["message" => "successful", "data" => $availableTimesInRequestedTimeZone]);
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
