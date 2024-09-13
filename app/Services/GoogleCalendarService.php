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
    public function createEvent($email, $startTime, $endTime, $date, $eventId)
    {
        $client = new Google_Client();
        $accessToken = 'ya29.a0AcM612zS78gduxAwE1iM-k4G5A2DIjIMI0p8ADE1_Ethe5XmIYmjriCxFiE1RMv4YqSLJ-Z4r4Ig-zAKpMZoFFh9RpW30Nk0fwVhFgJaSVoLeAeXHNhtGgD4_HbYPOEiG-xiDC8ZoFPRj-4oavBAnGQ617jZDZ6wD22E_BQNaCgYKAegSARESFQHGX2MiD5g2hIddvP6D4PbFbMsK0w0175';
        $refreshToken = '1//01-oystroJIVBCgYIARAAGAESNwF-L9IruGzKvf8sDB6KiygN4XO-P7r68VrYKu0Hkyd9Fzq_uJ4fJpp8O_P83hYKhxp8bmM1mUg';
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
                'dateTime' => Carbon::parse($date . $startTime)->toRfc3339String(),
                'timeZone' => 'Asia/Damascus',
            ]));
            $event->setEnd(new EventDateTime([
                'dateTime' => Carbon::parse($date . $endTime)->toRfc3339String(),
                'timeZone' => 'Asia/Damascus',
            ]));
            $attendee1 = new Google_Service_Calendar_EventAttendee();
            $attendee1->setEmail($email);
            $event->setAttendees([$attendee1]);

            $conference = new ConferenceData();
            $conferenceRequest = new CreateConferenceRequest();
            $conferenceSolutionKey = new ConferenceSolutionKey();
            $conferenceSolutionKey->setType('hangoutsMeet');
            $conferenceRequest->setConferenceSolutionKey($conferenceSolutionKey);
            $conferenceRequest->setRequestId('first');

            $conference->setCreateRequest($conferenceRequest);
            $event->setConferenceData($conference);

            $calendarId = 'primary';
            $event = $service->events->insert($calendarId, $event, ['conferenceDataVersion' => 1]);

            return [
                'eventId' => $event->getId(),
                'meetUrl' => $event->getHangoutLink(),
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
