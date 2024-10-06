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
        if ($eventId == 0){
            $event = new Google_Service_Calendar_Event();
            $event->setSummary('Futuer coder');
            
            $event->setDescription('
The lesson appointment has been successfully booked. Don’t forget to arrive on time, we wish you continued success!');
            $event->setStart(new EventDateTime([
                'dateTime' => Carbon::parse($date . $startTime,'UTC')
                                        ->setTimezone($userTimezone)
                                        ->toRfc3339String(),
                'timeZone' => $userTimezone,
            ]));
            $event->setEnd(new EventDateTime([
                'dateTime' => Carbon::parse($date . $endTime,'UTC')
                                        ->setTimezone($userTimezone)
                                        ->toRfc3339String(),
                'timeZone' => $userTimezone,
            ]));
            $attendee1 = new EventAttendee();
            $attendee1->setEmail($email); 
            $attendees = $event->getAttendees() ?? [];
            $permanentEmail = 'obedah9600@gmail.com';
            if (!in_array($permanentEmail, array_map(function($attendee) {
                return $attendee->getEmail();
            }, $attendees))) {
                
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

            $calendarId = 'primary';

            try {
                $createdEvent = $service->events->insert($calendarId, $event, [
                    'conferenceDataVersion' => 1,
                    'sendUpdates' => 'all' 
                ]);
            
                echo 'Event created: ' . $createdEvent->htmlLink;
                echo 'Join the meeting: ' . $createdEvent->getHangoutLink();
            
              
                return [
                    'eventId' => $createdEvent->getId(),
                    'meetUrl' => $createdEvent->getHangoutLink(),
                ];
            
            } catch (\Exception $e) {
                echo 'Error creating event: ' . $e->getMessage();
                return [
                    'error' => 'Failed to create event',
                    'message' => $e->getMessage()
                ];
            }
            
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
