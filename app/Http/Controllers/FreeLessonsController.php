<?php


namespace App\Http\Controllers;
require __DIR__ . '/../../../vendor/autoload.php';

use App\Models\FreeLessons;
use App\Models\GuestUsers;
use App\Models\Cources;
use App\Models\Cources_time;
use App\Services\GoogleCalendarService;
use Google\Service\Batch\Script;
use Illuminate\Http\Request;
use App\Http\Controllers\GuestUsersController;
use App\Services\GuestUserService;
use Carbon\Carbon;

class FreeLessonsController extends Controller
{
    protected $calendarService;
    protected $guestUsersController;

    public function __construct(GuestUserService $guestUserService,GuestUsersController $guestUsersController, GoogleCalendarService $calendarService)
    {
        $this->guestUserService = $guestUserService;
        $this->guestUsersController = $guestUsersController;
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
        } else {
            $user = GuestUsers::find($freeLesson->userId);
            $course = Cources::find($freeLesson->courseId);
            $time = Cources_time::find($freeLesson->sessionTime);
        
            if (!$user || !$course || !$time) {
                $jsonData = ['message' => 'Associated data not found'];
            } else {
                
                $sessionDate = Carbon::parse($time->SessionTimings)->setTimezone($request->timezone);

                
                $startTime = Carbon::parse($time->SessionTimings . ' ' . $time->startTime)->setTimezone($request->timezone);
                $endTime = Carbon::parse($time->SessionTimings . ' ' . $time->endTime)->setTimezone($request->timezone);

                $jsonData = [
                    'status' => 'successful',
                    'courseName' => $course->title,
                    'guestUserName' => $user->firstName . ' ' . $user->lastName,
                    'userAge' => $user->age,
                    'userEmail' => $user->email,
                    'lessonDate' => $sessionDate->toDateString(), 
                    'lessoStartTime' => $startTime->toDateTimeString(),
                    'lessoEndTime' => $endTime->toDateTimeString(),
                    'googleMeetUrl' => $freeLesson->meetUrl
                ];
            }
        }
    } else {
        $freeLesson = FreeLessons::paginate(5);
        $jsonData = ['status' => 'success', 'total' => $freeLesson->total(), 'data' => []];
        
        foreach ($freeLesson as $lesson) {
            $user = GuestUsers::find($lesson->userId);
            $course = Cources::find($lesson->courseId);
            $time = Cources_time::find($lesson->sessionTime);

           
            $sessionDate = Carbon::parse($time->SessionTimings)->setTimezone($request->timezone);
            $startTime = Carbon::parse($time->SessionTimings . ' ' . $time->startTime)->setTimezone($request->timezone);
            $endTime = Carbon::parse($time->SessionTimings . ' ' . $time->endTime)->setTimezone($request->timezone);

            $jsonData['data'][] = [
                'freeLessonId' => $lesson->id,
                'courseName' => $course->title,
                'guestUserName' => $user->firstName . ' ' . $user->lastName,
                'userAge' => $user->age,
                'userEmail' => $user->email,
                'lessonDate' => $sessionDate->toDateString(),
                'lessoStartTime' => $startTime->toDateTimeString(),
                'lessoEndTime' => $endTime->toDateTimeString(),
                'googleMeetUrl' => $lesson->meetUrl
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
        $eventDetails = $calendarService->createEvent($user->email, $time->startTime, $time->endTime, $time->SessionTimings, 0,$user->timeZone);
        
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
     *
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
            'courseId' => 'required',
            'sessionTimings' => 'required',
            'firstName' => 'required',
            'lastName' => 'required',
            'age' => 'required',
            'email' => 'required|email',
            'timeZone' => 'required',
        ]);

        $guestUserData = $request->all();
        $guestUser = $this->guestUserService->createGuestUser($guestUserData);
        return response()->json(["status"=>"chek your mailbox to verify your email","data"=>$guestUser]);
    }

}
