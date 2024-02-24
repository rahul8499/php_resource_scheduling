<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FacultySchedulePublished extends Mailable
{
    use Queueable, SerializesModels;

    public $fromDate;
    public $toDate;
    public $locationName;
    public $facultyName;
    public $schedules;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($fromDate, $toDate, $locationName, $facultyName, $schedules)
{
    // echo"schedules-->";print_r($schedules);exit;
    $this->fromDate = $fromDate;
    $this->toDate = $toDate;
    $this->locationName = $locationName;
    $this->facultyName = $facultyName;
    $this->schedules = $schedules;
}

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Faculty Schedule Published',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.faculty_schedule_published',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
    public function build()
{
    return $this->subject('Schedule Published')->view('emails.faculty_schedule_published');
}
}
