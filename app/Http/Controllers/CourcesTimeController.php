<?php

namespace App\Http\Controllers;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

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
        try {
            
            $request->validate([
                'courseId' => 'required',
                'SessionTimings' => 'required|date',
                'startTime' => 'required|date_format:H:i:s',
                'endTime' => 'required|date_format:H:i:s',
                'timeZone' => 'required'
            ]);
    
            $sessionDate = $request->input('SessionTimings');
            $startTime = $request->input('startTime');
            $endTime = $request->input('endTime');
            $timeZone = $request->input('timeZone');
    
            $startTimeFull = $sessionDate . ' ' . $startTime;
            $endTimeFull = $sessionDate . ' ' . $endTime;
    
            $startTimeTokyo = Carbon::createFromFormat('Y-m-d H:i:s', $startTimeFull, $timeZone);
            $endTimeTokyo = Carbon::createFromFormat('Y-m-d H:i:s', $endTimeFull, $timeZone);
    
            $startTimeUTC = $startTimeTokyo->setTimezone('UTC');
            $endTimeUTC = $endTimeTokyo->setTimezone('UTC');
            
            $sessionDateUTC = $startTimeTokyo->setTimezone('UTC')->toDateString();
    
            $course_time = Cources_time::create([
                'courseId' => $request->courseId,
                'SessionTimings' => $sessionDateUTC,
                'startTime' => $startTimeUTC->toTimeString(),
                'endTime' => $endTimeUTC->toTimeString(),
            ]);
    
            $course = Cources::find($request->courseId);
    
            return response()->json([
                'message' => 'Course time created successfully!',
                'course_name' => $course->title,
                'data' => $course_time
            ]);
    
        } catch (QueryException $e) {
            
            if ($e->getCode() == 23000) {
                return response()->json([
                    'message' => 'The selected time already exists for this course and date. Please choose a different time.'
                ], 400);
            }
    
            return response()->json([
                'message' => 'An error occurred while creating the course time. Please try again.'
            ], 500);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed. Please check the input data.'
            ], 422);
        }
    }
    


    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
        $newTime = Cources_time::find($id);

        if (!$newTime) {
            return response()->json(['message' => 'Time not found'], 404);
        }

       // convert the time format into UTC before saving it in database
        $newTime->SessionTimings = Carbon::parse($request->SessionTimings, $request->timeZone)->setTimezone('UTC')->toDateString();
        $newTime->startTime = Carbon::parse($request->start_time, $request->timeZone)->setTimezone('UTC')->toTimeString();
        $newTime->endTime = Carbon::parse($request->end_time, $request->timeZone)->setTimezone('UTC')->toTimeString();

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

        
        $nowInRequestedTimeZone = Carbon::now($timezone);
        $nowUTC = $nowInRequestedTimeZone->copy()->setTimezone('UTC');

       
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

       
        $availableTimesInRequestedTimeZone = $availableTimes->map(function ($time) use ($timezone) {
            
            $startDateTimeUTC = Carbon::parse($time->SessionTimings . ' ' . $time->startTime, 'UTC');
            $endDateTimeUTC = Carbon::parse($time->SessionTimings . ' ' . $time->endTime, 'UTC');

           
            $startDateTimeInRequestedTimezone = $startDateTimeUTC->setTimezone($timezone);
            $endDateTimeInRequestedTimezone = $endDateTimeUTC->setTimezone($timezone);

            return [
                'SessionTimings' => $startDateTimeInRequestedTimezone->toDateString(), 
                'startTime' => $startDateTimeInRequestedTimezone->toTimeString(), 
                'endTime' => $endDateTimeInRequestedTimezone->toTimeString(), 
                'studentsCount' => $time->studentsCount,
                'id' => $time->id
            ];
        });

        return response()->json(["message" => "successful", "data" => $availableTimesInRequestedTimeZone]);
    }
    public function getAvailableTimeZoneForAdmin(Request $request)
    {
        if (!$request->timezone) {
            return response()->json(['message' => 'Timezone is required'], 400);
        }
    
        $timezone = $request->timezone;
    
        
        $availableTimes = Cources_time::where('courseId', $request->course_id)
            ->paginate(10, ['SessionTimings', 'startTime', 'endTime', 'studentsCount', 'id']);
    
        
        $availableTimes->getCollection()->transform(function ($time) use ($timezone) {
           
            $startDateTimeUTC = Carbon::parse($time->SessionTimings . ' ' . $time->startTime, 'UTC');
            $endDateTimeUTC = Carbon::parse($time->SessionTimings . ' ' . $time->endTime, 'UTC');
    
           
            $startDateTimeInRequestedTimezone = $startDateTimeUTC->setTimezone($timezone);
            $endDateTimeInRequestedTimezone = $endDateTimeUTC->setTimezone($timezone);
    
            return [
                'SessionTimings' => $startDateTimeInRequestedTimezone->toDateString(), 
                'startTime' => $startDateTimeInRequestedTimezone->toTimeString(), 
                'endTime' => $endDateTimeInRequestedTimezone->toTimeString(),
                'id' => $time->id
            ];
        });
    
        return response()->json([
            "message" => "successful",
            "data" => $availableTimes->items(), 
            "current_page" => $availableTimes->currentPage(),
            "last_page" => $availableTimes->lastPage(), 
            "per_page" => $availableTimes->perPage(), 
            "total" => $availableTimes->total() 
        ]);
    }

    public function getAllTimes(Request $request)
{
    if (!$request->timezone) {
        return response()->json(['message' => 'Timezone is required'], 400);
    }

    $timezone=$request->timezone;
    $availableTimes = Cources_time::with(['course:id,title'])
        ->paginate(10, ['SessionTimings', 'startTime', 'endTime', 'id', 'courseId', 'studentsCount']);

    if ($availableTimes->isEmpty()) {
        return response()->json([
            "message" => "successful",
            "data" => [],
            "current_page" => 1,
            "last_page" => 1,
            "per_page" => 10,
            "total" => 0
        ]);
    }

    
    $availableTimes->getCollection()->transform(function ($time) use ($timezone) {
        $startDateTimeUTC = Carbon::parse($time->SessionTimings . ' ' . $time->startTime, 'UTC');
        $endDateTimeUTC = Carbon::parse($time->SessionTimings . ' ' . $time->endTime, 'UTC');

        $startDateTimeInRequestedTimezone = $startDateTimeUTC->setTimezone($timezone);
        $endDateTimeInRequestedTimezone = $endDateTimeUTC->setTimezone($timezone);

        return [
            'SessionTimings' => $startDateTimeInRequestedTimezone->toDateString(),
            'startTime' => $startDateTimeInRequestedTimezone->toTimeString(),
            'endTime' => $endDateTimeInRequestedTimezone->toTimeString(),
            'courseId' => $time->courseId,
            'courseName' => $time->course->title,
            'studentsCount' => $time->studentsCount,
            'id' => $time->id
        ];
    });

    return response()->json([
        "message" => "successful",
        "data" => $availableTimes->items(),
        "current_page" => $availableTimes->currentPage(),
        "last_page" => $availableTimes->lastPage(),
        "per_page" => $availableTimes->perPage(),
        "total" => $availableTimes->total()
    ]);
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
