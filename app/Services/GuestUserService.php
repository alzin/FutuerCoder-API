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
        $sessionTimings=$data['sessionTimings'];

        $verificationUrl = url("/api/verify-guest-email/{$verificationToken}/{$courseId}/{$sessionTimings}");
        Mail::to($guestUser->email)->send(new VerifyEmail($verificationUrl));

        return $guestUser;
    }

        public function verifyGuestUser($token, $courseId, $sessionTimings)
    {
        $guestUser = GuestUsers::where('verification_token', $token)->first();

        if (!$guestUser) {
            return [
                'status' => 'error',
                'message' => 'Invalid or expired verification token.',
                'statusCode' => 400
            ];
        }

        $guestUser->update([
            'email_verified_at' => now(),
            'verification_token' => null,
            'email_verified' => 1
        ]);

        $existingtime = Cources_time::where('courseId', $courseId)
            ->where('id', $sessionTimings)
            ->where('studentsCount', '<', 3)
            ->first();

        if (!$existingtime) {
            return [
                'status' => 'error',
                'message' => 'No available session time found.',
                'statusCode' => 404
            ];
        }

        $existingLesson = FreeLessons::where('sessionTime', $existingtime->id)->first();

        if ($existingLesson && $existingtime->studentsCount < 3) {
            $eventDetails = $this->calendarService->createEvent($guestUser->email, $existingtime->startTime, $existingtime->endTime, $existingtime->SessionTimings, $existingLesson->eventId, $guestUser->timeZone);
        } else {
            $eventDetails = $this->calendarService->createEvent($guestUser->email, $existingtime->startTime, $existingtime->endTime, $existingtime->SessionTimings, 0, $guestUser->timeZone);
        }

        $existingtime->increment('studentsCount');
        $freeLesson = FreeLessons::create([
            'courseId' => $courseId,
            'userId' => $guestUser->id,
            'sessionTime' => $existingtime->id,
            'meetUrl' => $eventDetails['meetUrl'],
            'eventId' => $eventDetails['eventId']
        ]);

        return [
            'status' => 'success',
            'guestUser' => $guestUser,
            'sessionDetails' => [
                'sessionTime' => $existingtime->SessionTimings,
                'meetUrl' => $eventDetails['meetUrl'],
                'eventId' => $eventDetails['eventId']
            ]
        ];
    }

}
