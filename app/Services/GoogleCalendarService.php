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
use App\Mail\EventAttendeeMail;
use Illuminate\Support\Facades\Mail;


class GoogleCalendarService
{
    public function createEvent($email, $startTime, $endTime, $date, $eventId, $userTimezone)
{
    $client = new Google_Client();
    $accessToken = env('GOOGLEACCESSTOKEN');
    $refreshToken = env('GOOGLEREFRESHTOKEN');
    $client->setAccessToken([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'expires_in' => 36000000,
    ]);

    if ($client->isAccessTokenExpired()) {
        $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (isset($newToken['access_token'])) {
            $accessToken = $newToken['access_token'];
            $client->setAccessToken($newToken);
        }
    } else {
        return response()->json(['error' => 'Unable to refresh access token'], 500);
    }

    $client->setApplicationName('laravelcalendar');
    $client->setClientId('YOUR_CLIENT_ID');
    $client->setClientSecret('YOUR_CLIENT_SECRET');
    $client->setRedirectUri('YOUR_REDIRECT_URI');
    $client->setScopes(Google_Service_Calendar::CALENDAR);
    $client->setAccessType('offline');
    $client->setApprovalPrompt('force');
    $service = new Google_Service_Calendar($client);

    if ($eventId == 0) {
        $event = new Google_Service_Calendar_Event();
        $event->setSummary('Future Coder');
        $event->setDescription('The lesson appointment has been successfully booked. Don’t forget to arrive on time, we wish you continued success!');
        $event->setStart(new EventDateTime([
            'dateTime' => Carbon::parse($date . $startTime, 'UTC')
                                ->setTimezone($userTimezone)
                                ->toRfc3339String(),
            'timeZone' => $userTimezone,
        ]));
        $event->setEnd(new EventDateTime([
            'dateTime' => Carbon::parse($date . $endTime, 'UTC')
                                ->setTimezone($userTimezone)
                                ->toRfc3339String(),
            'timeZone' => $userTimezone,
        ]));

        $attendee1 = new EventAttendee();
        $attendee1->setEmail($email);
        $attendees = $event->getAttendees() ?? [];
        $permanentEmail = 'futurecoderonlineschool@gmail.com';
        $permanentEmailExists = false;

        foreach ($attendees as $attendee) {
            if ($attendee->getEmail() === $permanentEmail) {
                $permanentEmailExists = true;
                break;
            }
        }

        if (!$permanentEmailExists) {
            $attendeePermanent = new EventAttendee();
            $attendeePermanent->setEmail($permanentEmail);
            $attendees[] = $attendeePermanent;
        }

        $event->setAttendees(array_merge([$attendee1], $attendees));

        $conference = new ConferenceData();
        $conferenceRequest = new CreateConferenceRequest();
        $conferenceSolutionKey = new ConferenceSolutionKey();
        $conferenceSolutionKey->setType('hangoutsMeet');
        $conferenceRequest->setConferenceSolutionKey($conferenceSolutionKey);
        $conferenceRequest->setRequestId(uniqid());
        $conference->setCreateRequest($conferenceRequest);
        $event->setConferenceData($conference);

        $calendarId = 'b8913a0fc91696496e801350a53e347f62008e4daf3bf91b45cd7067ded46563@group.calendar.google.com';

        try {
            $createdEvent = $service->events->insert($calendarId, $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'all'
            ]);

            $eventDetails = [
                'title' => $createdEvent->getSummary(),
                'startTime' => $createdEvent->getStart()->getDateTime(),
                'endTime' => $createdEvent->getEnd()->getDateTime(),
                'meetUrl' => $createdEvent->getHangoutLink(),
            ];

            foreach ($createdEvent->getAttendees() as $attendee) {
                Mail::to($attendee->getEmail())->send(new EventAttendeeMail($eventDetails));
            }

            return [
                'eventId' => $createdEvent->getId(),
                'meetUrl' => $createdEvent->getHangoutLink(),
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to create event',
                'message' => $e->getMessage()
            ];
        }

    } else {
        $event = $service->events->get('YOUR_CALENDAR_ID', $eventId);
        $attendee = new Google_Service_Calendar_EventAttendee();
        $attendee->setEmail($email);

        $attendees = $event->getAttendees();
        $attendees[] = $attendee;
        $event->setAttendees($attendees);

        $service->events->update('b8913a0fc91696496e801350a53e347f62008e4daf3bf91b45cd7067ded46563@group.calendar.google.com', $eventId, $event);

        
        $eventDetails = [
            'title' => $event->getSummary(),
            'startTime' => $event->getStart()->getDateTime(),
            'endTime' => $event->getEnd()->getDateTime(),
            'meetUrl' => $event->getHangoutLink(),
        ];

        foreach ($attendees as $attendee) {
            Mail::to($attendee->getEmail())->send(new EventAttendeeMail($eventDetails));
        }

        return [
            'eventId' => $eventId,
            'meetUrl' => $event->getHangoutLink(),
        ];
    }
}


}
