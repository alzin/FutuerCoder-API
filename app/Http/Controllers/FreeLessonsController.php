<?php


namespace App\Http\Controllers;
require __DIR__ . '/../../../vendor/autoload.php';

use App\Models\FreeLessons;
use App\Models\GuestUsers;
use App\Models\Cources;
use App\Models\Cources_time;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class FreeLessonsController extends Controller
{
    protected $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {   
        if($request->has('id')){
            $freeLesson = FreeLessons::find($request->id);
        
            if(!$freeLesson){
                $jsonData = ['message' => 'lesson not found'];
            } 
            else 
            {
                $user = GuestUsers::find($freeLesson->userId);
                $course = Cources::find($freeLesson->courseId);
                $time = Cources_time::find($freeLesson->sessionTime);
        
                if (!$user || !$course || !$time) {
                    $jsonData = ['message' => 'Associated data not found'];
                } else {
                    $jsonData = [
                        'status' => 'successful',
                        'courseName' => $course->title,
                        'guestUserName' => $user->firstName . ' ' . $user->lastName,
                        'userAge' => $user->age,
                        'userEmail' => $user->email,
                        'lessonDate' => $time->SessionTimings,
                        'lessoStartTime'=>$time->startTime,
                        'lessoEndTime'=>$time->endTime,
                        'googleMeetUrl' => $freeLesson->meetUrl
                    ];
                }
            }
        }
        
        else{
                $freeLesson = FreeLessons::paginate(5);
                $jsonData = ['status' => 'success','total'=>$freeLesson->total(), 'data' => []];
                
                foreach ($freeLesson as $lesson) {
                    $user = GuestUsers::find($lesson->userId);
                    $course = Cources::find($lesson->courseId);
                    $time=Cources_time::find($lesson->sessionTime);
                
                    $jsonData['data'][] = [
                        'freeLessonId'=>$lesson->id,
                        'courseName' => $course->title,
                        'guestUserName' => $user->firstName . ' ' . $user->lastName,
                        'userAge'=>$user->age,
                        'userEmail'=>$user->email,
                        'lessonDate'=>$time->SessionTimings,
                        'lessoStartTime'=>$time->startTime,
                        'lessoEndTime'=>$time->endTime,
                        'googleMeetUrl'=>$lesson->meetUrl
                    ];
                }
            }
        return response()->json($jsonData);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request,GoogleCalendarService $calendarService)
    {
        $request->validate([
            'userId' => 'required|integer',
            'courseId' => 'required|integer',
            'time' => 'required'
        ]);
        $user=GuestUsers::find($request->userId);
        $course=Cources::find($request->courseId);
        $time=Cources_time::find($request->time);
        $eventDetails = $calendarService->createEvent($user->email, $time->startTime, $time->endTime, $time->SessionTimings, 0);
        
        $freeLesson = FreeLessons::create([
            'userId' => $user->id,
            'courseId' => $course->id,
            'sessionTime'=>$time->id,
            'meetUrl' => $eventDetails['meetUrl'],
        ]);

        return response()->json([
            'message' => 'free lesson created successfuly',
            'course name' =>$course->title,
            'guest user name'=>$user->firstName .''. $user->lastName,
            'session time'=>[$time->startTime ,$time->endTime,$time->SessionTimings],
             'data' => $freeLesson]
            , 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        $request->validate([
            'sessionTime'=>'required',
            'meetUrl'=>'required'
        ]);
            $freeLesson=FreeLessons::find($id);
            $freeLesson->sessionTime= $request->sessionTime;
            $freeLesson->meetUrl=$request->meetUrl;
            $freeLesson->save();

            return response()->json([
                    'message'=>'success',
                    'data'=>$freeLesson
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id'=>'required'
        ]);
        $freeLesson = FreeLessons::find($request->id);
        if (!$freeLesson) {
            return response()->json(['message' => 'free Lesson not found'], 404);
        }
        $freeLesson->forceDelete();
        return response()->json(['message' => 'free Lesson deleted']);
    }
    public function createSession(Request $request)
    {
        $request->validate([
            'CourseId' => 'required',
            'SessionTimings' => 'required',
            'guestUserId'=>'required'
        ]);
        
        $guestUser = GuestUsers::find($request->guestUserId);

        if($guestUser->email_verified==1)
        {
            //search to check if there exsists time available for lesson
            $existingtime = Cources_time::where('courseId', $request->CourseId)
                ->where('id', $request->SessionTimings)
                ->where('studentsCount', '<', 3) 
                ->first();

            if (!$existingtime) {
                return response()->json(['message' => 'No available session time found'], 404);
            }

            //search if there a sission for join to it
            $existingLesson = FreeLessons::where('sessionTime', $existingtime->id)->first();

            if ($existingLesson && $existingtime->studentsCount < 3) {
                $meetUrl = $existingLesson->meetUrl;
                $eventId = $existingLesson->eventId;
                $startTime = $existingtime->startTime;
                $endTime = $existingtime->endTime;
                $date = $existingtime->SessionTimings;
                $eventDetails = $this->calendarService->createEvent($guestUser->email, $startTime, $endTime, $date, $eventId);
                $existingtime->increment('studentsCount');
                //if we not found a previos session we will create a new session
            } else {
                $startTime = $existingtime->startTime;
                $endTime = $existingtime->endTime;
                $date = $existingtime->SessionTimings;
                $eventDetails = $this->calendarService->createEvent($guestUser->email, $startTime, $endTime, $date, $eventId = 0);
                $eventId = $eventDetails['eventId'];
                $meetUrl = $eventDetails['meetUrl'];
                $existingtime->increment('studentsCount');
            }

            $freeLesson = FreeLessons::create([
                'courseId' => $request->CourseId,
                'userId' => $guestUser->id,
                'sessionTime' => $existingtime->id,
                'meetUrl' => $meetUrl,
                'eventId'=>$eventId
            ]);

            if ($freeLesson) {
                return response()->json([
                    'message' => 'Session created successfully',
                    'userdata' => $guestUser,
                    'sessionData' => $freeLesson
                ], 200);
            } else {
                return response()->json(['message' => 'Cannot create a free lesson'], 500);
            }
        }
        else{
            return response()->json(['message' => 'guest user not vefification'], 500);
        }    
    }   

}