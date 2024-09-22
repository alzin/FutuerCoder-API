<?php

namespace App\Services;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_EventAttendee;
use Google_Service_Calendar_ConferenceData;
use Google_Service_Calendar_CreateConferenceRequest;
use Google_Service_Calendar_ConferenceSolutionKey;
use Google\Service\Calendar;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceSolutionKey;


class GoogleCalendarService
{
    public function createEvent($email, $startTime, $endTime, $date, $eventId,$userTimezone)
    {
        $client = new Google_Client();
        $accessToken = env('GOOGLEACCESSTOKEN');
        $refreshToken = env('GOOGLEREFRESHTOKEN');
        $client->setAccessToken([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600,
        ]);

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (isset($newToken['access_token'])) {
                $accessToken = $newToken['access_token'];
                $client->setAccessToken($newToken);
            }
        }
        else {
            return response()->json(['error' => 'Unable to refresh access token'], 500);
        }

        $client->setApplicationName('laravelcalendar');
        $client->setClientId('691463698835-fum9nttru55k9k7pfvf7j9ef51slslod.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri('https://www.google.com');
        $client->setScopes(Google_Service_Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

        $service = new Google_Service_Calendar($client);

        if ($eventId == 0) {
            $event = new Google_Service_Calendar_Event();
            $event->setSummary('NEW EVENT');
            
            $event->setDescription('Event description');
            $event->setStart(new EventDateTime([
                'dateTime' => Carbon::parse($date . $startTime,'UTC')
                                        ->addHours(6)
                                        ->setTimezone($userTimezone)
                                        ->toRfc3339String(),
                'timeZone' => $userTimezone,    
            ]));
            $event->setEnd(new EventDateTime([
                'dateTime' => Carbon::parse($date . $endTime,'UTC')
                                        ->addHours(6)
                                        ->setTimezone($userTimezone)
                                        ->toRfc3339String(),
                'timeZone' => $userTimezone,
            ]));
            $attendee1 = new EventAttendee();
            $attendee1->setEmail($email); // البريد الإلكتروني للمدعو
            $event->setAttendees([$attendee1]);

            // إعداد الاجتماع عبر Google Meet
            $conference = new ConferenceData();
            $conferenceRequest = new CreateConferenceRequest();
            $conferenceSolutionKey = new ConferenceSolutionKey();
            $conferenceSolutionKey->setType('hangoutsMeet');
            $conferenceRequest->setConferenceSolutionKey($conferenceSolutionKey);
            $conferenceRequest->setRequestId(uniqid()); // معرف فريد للطلب
            $conference->setCreateRequest($conferenceRequest);
            $event->setConferenceData($conference);

            $calendarId = 'primary';

            try {
                // إدراج الحدث مع إرسال الدعوات عبر البريد الإلكتروني
                $createdEvent = $service->events->insert($calendarId, $event, [
                    'conferenceDataVersion' => 1,
                    'sendUpdates' => 'all' // إرسال دعوة إلى جميع الحضور عبر البريد الإلكتروني
                ]);

                // التأكيد على نجاح الإنشاء وعرض الرابط
                echo 'Event created: ' . $createdEvent->htmlLink;
                echo 'Join the meeting: ' . $createdEvent->getHangoutLink();

            } catch (\Exception $e) {
                echo 'Error creating event: ' . $e->getMessage();
            }
            return [
                'eventId' => $createdEvent->getId(),
                'meetUrl' => $createdEvent->getHangoutLink(),
            ];
        } else {
            $event = $service->events->get('primary', $eventId);
            $attendee = new Google_Service_Calendar_EventAttendee();
            $attendee->setEmail($email);

            $attendees = $event->getAttendees();
            $attendees[] = $attendee;
            $event->setAttendees($attendees);

            $service->events->update('primary', $eventId, $event);

            return [
                'eventId' => $eventId,
                'meetUrl' => $event->getHangoutLink(),
            ];
        }
    }
}
