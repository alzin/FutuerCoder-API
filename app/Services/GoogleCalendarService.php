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
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->setScopes(Google_Service_Calendar::CALENDAR);
        $client->setAccessType('offline');
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
        }
    
        $client->setApplicationName('laravelcalendar');
        $service = new Google_Service_Calendar($client);
    
        
        if ($eventId == 0) {
            $calendarId = 'primary'; // Use the user's primary calendar
    
            $existingEvents = $service->events->listEvents($calendarId, [
                'timeMin' => Carbon::parse($date . $startTime, 'UTC')->toRfc3339String(),
                'timeMax' => Carbon::parse($date . $endTime, 'UTC')->toRfc3339String(),
                'q' => 'Future Coder', 
            ]);
        
            if (count($existingEvents->getItems()) > 0) {
                
                return response()->json(['message' => 'Event already exists'], 400);
            }
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
    
            
            $permanentEmail = 'futurecoderonlineschool@gmail.com';
            $attendees = $event->getAttendees() ?? [];
            $existingEmails = array_map(function($attendee) {
                return $attendee->getEmail();
            }, $attendees);
    
            if (!in_array($permanentEmail, $existingEmails)) {
                $attendeePermanent = new EventAttendee();
                $attendeePermanent->setEmail($permanentEmail);
                $attendees[] = $attendeePermanent;
            }
    
            if (!in_array($email, $existingEmails)) {
                $attendees[] = $attendee1;
            }
    
            $event->setAttendees($attendees);
    
            
            $conference = new ConferenceData();
            $conferenceRequest = new CreateConferenceRequest();
            $conferenceSolutionKey = new ConferenceSolutionKey();
            $conferenceSolutionKey->setType('hangoutsMeet');
            $conferenceRequest->setConferenceSolutionKey($conferenceSolutionKey);
            $conferenceRequest->setRequestId(uniqid());
            $conference->setCreateRequest($conferenceRequest);
            $event->setConferenceData($conference);
    
          
           
            try {
                $createdEvent = $service->events->insert($calendarId, $event, [
                    'conferenceDataVersion' => 1,
                ]);
    
                
                $eventDetails = [
                    'title' => $createdEvent->getSummary(),
                    'startTime' => $createdEvent->getStart()->getDateTime(),
                    'endTime' => $createdEvent->getEnd()->getDateTime(),
                    'meetUrl' => $createdEvent->getHangoutLink(),
                ];
    
               
                foreach ($attendees as $attendee) {
                    if ($attendee->getEmail() === $email || $attendee->getEmail() === $permanentEmail) {
                        Mail::to($attendee->getEmail())->send(new EventAttendeeMail($eventDetails));
                    }
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
            $calendarId = 'primary'; // Use the user's primary calendar
            $event = $service->events->get($calendarId, $eventId);
    
            $attendees = $event->getAttendees();
            $existingEmails = array_map(function($attendee) {
                return $attendee->getEmail();
            }, $attendees);
    
            if (!in_array($email, $existingEmails)) {
                $attendee = new Google_Service_Calendar_EventAttendee();
                $attendee->setEmail($email);
                $attendees[] = $attendee;
            }
    
            $event->setAttendees($attendees);
    
            
            $service->events->update($calendarId, $eventId, $event);
    
           
            $eventDetails = [
                'title' => $event->getSummary(),
                'startTime' => $event->getStart()->getDateTime(),
                'endTime' => $event->getEnd()->getDateTime(),
                'meetUrl' => $event->getHangoutLink(),
            ];
            
            $permanentEmail='futurecoderonlineschool@gmail.com';
            foreach ($attendees as $attendee) {
                if ($attendee->getEmail() === $email || $attendee->getEmail() === $permanentEmail) {
                    Mail::to($attendee->getEmail())->send(new EventAttendeeMail($eventDetails));
                }
            }
    
            return [
                'eventId' => $eventId,
                'meetUrl' => $event->getHangoutLink(),
            ];
        }
    }
    


}
