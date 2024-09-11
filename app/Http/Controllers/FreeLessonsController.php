<?php


namespace App\Http\Controllers;
require __DIR__ . '/../../../vendor/autoload.php';

use App\Models\FreeLessons;
use App\Models\GuestUsers;
use App\Models\Cources;
use App\Models\Cources_time;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar as Google_Service_Calendar;
use Google\Service\Calendar\Event as Google_Service_Calendar_Event;
use Google_Service_Calendar_ConferenceData;
use Google_Service_Calendar_CreateConferenceRequest;
use Google_Service_Calendar_ConferenceSolutionKey;
use Google_Service_Calendar_EventAttendee;
use Google\Service\Calendar\EventDateTime as Google_Service_Calendar_EventDateTime;
use Illuminate\Support\Str;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\Mail;



use Spatie\GoogleCalendar\GoogleCalendar;

use Illuminate\Http\Request;

class FreeLessonsController extends Controller
{

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
        
                // تحقق من أن البيانات المرتبطة قد تم العثور عليها
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
    public function create(Request $request)
    {
        $request->validate([
            'userId' => 'required|integer',
            'courseId' => 'required|integer',
            'time' => 'required'
        ]);
        $user=GuestUsers::find($request->userId);
        $course=Cources::find($request->courseId);
        $time=Cources_time::find($request->time);
        
        $freeLesson = FreeLessons::create([
            'userId' => $user->id,
            'courseId' => $course->id,
            'sessionTime'=>$time->id,
            'meetUrl' =>$this->createEvent()
        ]);

        return response()->json([
            'message' => 'free lesson created successfuly',
            'course name' =>$course->title,
            'guest user name'=>$user->firstName . $user->lastName,
            'session time'=>[$time->startTime ,$time->endTime,$time->SessionTimings],
             'data' => $freeLesson]
            , 201);
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
    public function show(FreeLessons $freeLessons)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FreeLessons $freeLessons)
    {
        //
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
        // التحقق من المدخلات
        $request->validate([
            'CourseId' => 'required',
            'SessionTimings' => 'required',
            'guestUserId'=>'required'
        ]);
        
        // إنشاء مستخدم ضيف جديد
        $guestUser = GuestUsers::find($request->guestUserId);

        if($guestUser->email_verified==1)
        {
            // البحث عن وقت متاح للجلسة بناءً على courseId و SessionTimings
            $existingtime = Cources_time::where('courseId', $request->CourseId)
                ->where('id', $request->SessionTimings)
                ->where('studentsCount', '<', 3) // تأكد أن عدد الطلاب أقل من 3
                ->first();

            if (!$existingtime) {
                return response()->json(['message' => 'No available session time found'], 404);
            }

            // البحث عن جلسة سابقة مرتبطة بهذا الوقت
            $existingLesson = FreeLessons::where('sessionTime', $existingtime->id)->first();

            if ($existingLesson && $existingtime->studentsCount < 3) {
                // استخدام نفس رابط Google Meet وزيادة عدد الطلاب
                $meetUrl = $existingLesson->meetUrl;
                $eventId= $existingLesson->eventId;
                $startTime=$existingtime->startTime;
                $endTime=$existingtime->endTime;
                $date=$existingtime->SessionTimings;
                $eventDetails = $this->createEvent($guestUser->email,$startTime,$endTime,$date,$eventId);
                
                $existingtime->increment('studentsCount');
            } else {
                $startTime=$existingtime->startTime;
                $endTime=$existingtime->endTime;
                $date=$existingtime->SessionTimings;
                // إذا لم نجد جلسة مطابقة أو إذا كان عدد الطلاب قد وصل إلى 3، قم بإنشاء اجتماع Google Meet جديد
                $eventDetails = $this->createEvent($guestUser->email,$startTime,$endTime,$date,$eventId=0);
                $eventId = $eventDetails['eventId'];
                $meetUrl = $eventDetails['meetUrl'];
                $existingtime->increment('studentsCount');
            }

            // إنشاء جلسة جديدة
            $freeLesson = FreeLessons::create([
                'courseId' => $request->CourseId,
                'userId' => $guestUser->id,
                'sessionTime' => $existingtime->id,
                'meetUrl' => $meetUrl,
                'eventId'=>$eventId
            ]);

            // التحقق من نجاح العملية
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


    
    public function createEvent($email,$startTime,$endTime,$date,$eventId)
    {   
       
            $client = new Client();
            $accessToken = 'ya29.a0AcM612xp3WCOiwYKUNi2AgxmySj7v6J4kKAukqHAlrctLRrL8F3orf9rB689JxT_EWNR9F73D1xIAxn7Vyb4EugMVMWT1WEGS2zCt8eGNb_crfDAYpUGL6pvhuFYo2_SHQx-yBYmMojbtmcBd0pe-LukDc_3470rO_rqHuvKaCgYKAYYSARESFQHGX2MizPZa108mL8wXYga4xLvl1A0175';
            $refreshToken='1//01xwXMk7x9ho2CgYIARAAGAESNwF-L9IrepOR90LGN8haT7sp-R1g91mmhYdyvy3oORUF5grMxUAPD9rX27Y-gUdMz6fl08P19jo';
            $client->setAccessToken([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => 3600, // مدة صلاحية الـ access token بالثواني
            ]);
            if ($client->isAccessTokenExpired()) {
                // يجدد الرمز المميز تلقائيًا باستخدام refresh token
                $client->fetchAccessTokenWithRefreshToken($refreshToken);
                
                // الحصول على الرمز المميز الجديد
                $newAccessToken = $client->getAccessToken();
            }
            //$client->setAccessToken();
            $client->setApplicationName('laravelcalendar');
            $client->setClientId('691463698835-fum9nttru55k9k7pfvf7j9ef51slslod.apps.googleusercontent.com');
            $client->setClientSecret('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');
            $client->setRedirectUri('https://www.google.com');
            $client->setScopes(Google_Service_Calendar::CALENDAR);
            $client->setAccessType('offline');
            $client->setApprovalPrompt('force');
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($refreshToken);
            }
          
            $service = new Google_Service_Calendar($client);
            if($eventId == 0)
            {
            $event = new Google_Service_Calendar_Event();
            $event->setSummary('NEW EVENT');
            $event->setDescription('Event description');
            $event->setStart(new Google_Service_Calendar_EventDateTime(array(
            'dateTime' =>Carbon::parse($date . $startTime)->toRfc3339String(),
            'timeZone' => 'Asia/Damascus',
            )));
            $event->setEnd(new Google_Service_Calendar_EventDateTime(array(
            'dateTime' => Carbon::parse($date . $endTime)->toRfc3339String(),
            'timeZone' => 'Asia/Damascus',
            )));
            $attendee1 = new Google_Service_Calendar_EventAttendee();
            $attendee1->setEmail($email);
            // إضافة المشارك إلى الحدث
            $event->setAttendees(array($attendee1));


            $conference = new Google_Service_Calendar_ConferenceData();
            $conferenceRequest = new Google_Service_Calendar_CreateConferenceRequest();
            $conferenceSolutionKey = new Google_Service_Calendar_ConferenceSolutionKey();

            $conferenceSolutionKey->setType('hangoutsMeet');
            $conferenceRequest->setConferenceSolutionKey($conferenceSolutionKey);
            $conferenceRequest->setRequestId('first'); // يجب أن يكون معرفًا فريدًا لكل طلب

            $conference->setCreateRequest($conferenceRequest);
            $event->setConferenceData($conference);

            $calendarId = 'primary';
            $event = $service->events->insert($calendarId, $event, array('conferenceDataVersion' => 1));
            $eventId = $event->getId();
            $meetUrl=$event->getHangoutLink();
            return array(
                'eventId' => $eventId,
                'meetUrl' => $meetUrl
            );
            
        }
        else{
            $event = $service->events->get('primary', $eventId);
        
        // إضافة المشارك إلى الحدث الموجود
        $attendee = new Google_Service_Calendar_EventAttendee();
        $attendee->setEmail($email);
        
        $attendees = $event->getAttendees();
        if ($attendees === null) {
            $attendees = array();
        }
        
        $attendees[] = $attendee;
        $event->setAttendees($attendees);
        
        // تحديث الحدث في تقويم جوجل
        $service->events->update('primary', $eventId, $event);
        
        // الحصول على رابط Google Meet إذا كان موجودًا
        $meetUrl = $event->getHangoutLink();
        
        return array(
            'eventId' => $eventId,
            'meetUrl' => $meetUrl
        );
        }
    }


}