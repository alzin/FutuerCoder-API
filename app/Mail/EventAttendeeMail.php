<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventAttendeeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $eventDetails;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($eventDetails)
    {
        $this->eventDetails = $eventDetails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('You have been added to an event!')
                    ->view('emails.event_attendee')
                    ->with('details', $this->eventDetails);
    }
}
