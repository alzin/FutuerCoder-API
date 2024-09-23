<?php

namespace App\Services;

use App\Models\GuestUsers;
use App\Models\Cources;
use App\Models\Cources_time;
use App\Models\FreeLessons;
use Illuminate\Support\Facades\Mail;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Str;
use App\Mail\VerifyEmail;
class GuestUserService
{
    protected $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function createGuestUser($data)
    {
        $verificationToken = Str::random(32);

        $guestUser = GuestUsers::create([
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'age' => $data['age'],
            'email' => $data['email'],
            'timeZone' => $data['timeZone'],
            'verification_token' => $verificationToken
        ]);
        $courseId=$data['courseId'];
        $SessionTimings=$data['SessionTimings'];

        $verificationUrl = url("/api/verify-guest-email/{$verificationToken}/{$courseId}/{$SessionTimings}");
        Mail::to($guestUser->email)->send(new VerifyEmail($verificationUrl));

        return $guestUser;
    }

    public function verifyGuestUser($token, $courseId, $sessionTimings)
    {
        $guestUser = GuestUsers::where('verification_token', $token)->first();

        if ($guestUser && $guestUser->email_verified == 1) {
            $existingtime = Cources_time::where('courseId', $courseId)
                ->where('id', $sessionTimings)
                ->where('studentsCount', '<', 3)
                ->first();

            if (!$existingtime) {
                return response()->json(['message' => 'No available session time found'], 404);
            }

            $existingLesson = FreeLessons::where('sessionTime', $existingtime->id)->first();

            if ($existingLesson && $existingtime->studentsCount < 3) {
                $eventDetails = $this->calendarService->createEvent($guestUser->email, $existingtime->startTime, $existingtime->endTime, $existingtime->SessionTimings, $existingLesson->eventId, $guestUser->timeZone);
                $existingtime->increment('studentsCount');
            } else {
                $eventDetails = $this->calendarService->createEvent($guestUser->email, $existingtime->startTime, $existingtime->endTime, $existingtime->SessionTimings, 0, $guestUser->timeZone);
                $existingtime->increment('studentsCount');
            }

            FreeLessons::create([
                'courseId' => $courseId,
                'userId' => $guestUser->id,
                'sessionTime' => $existingtime->id,
                'meetUrl' => $eventDetails['meetUrl'],
                'eventId' => $eventDetails['eventId']
            ]);
            return true;
        }

        return false;
    }
}
