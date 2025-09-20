<?php

namespace App\Jobs;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendMassEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60; // 1 minute per email
    public $tries = 3;

    protected string $email;
    protected array $message;
    protected string $recipientType;
    protected ?Student $student;
    protected string $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $email, array $message, string $recipientType, ?Student $student = null, string $jobId = null)
    {
        $this->email = $email;
        $this->message = $message;
        $this->recipientType = $recipientType;
        $this->student = $student;
        $this->jobId = $jobId ?? uniqid();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Sending mass email", [
                'job_id' => $this->jobId,
                'email' => $this->email,
                'type' => $this->recipientType
            ]);

            // Prepare email data
            $emailData = [
                'subject' => $this->message['subject'] ?? 'Important Notice',
                'body' => $this->message['body'] ?? '',
                'sender_name' => $this->message['sender_name'] ?? config('app.name'),
                'recipient_type' => $this->recipientType,
                'student' => $this->student,
            ];

            // Send email based on type
            if ($this->recipientType === 'student') {
                $this->sendStudentEmail($emailData);
            } elseif ($this->recipientType === 'parent') {
                $this->sendParentEmail($emailData);
            }

            Log::info("Mass email sent successfully", [
                'job_id' => $this->jobId,
                'email' => $this->email
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send mass email", [
                'job_id' => $this->jobId,
                'email' => $this->email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Send email to student.
     */
    private function sendStudentEmail(array $data): void
    {
        Mail::send('emails.student-notification', $data, function ($message) use ($data) {
            $message->to($this->email)
                ->subject($data['subject'])
                ->from(config('mail.from.address'), $data['sender_name']);
        });
    }

    /**
     * Send email to parent.
     */
    private function sendParentEmail(array $data): void
    {
        Mail::send('emails.parent-notification', $data, function ($message) use ($data) {
            $message->to($this->email)
                ->subject($data['subject'])
                ->from(config('mail.from.address'), $data['sender_name']);
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Mass email job failed permanently", [
            'job_id' => $this->jobId,
            'email' => $this->email,
            'error' => $exception->getMessage()
        ]);
    }
}
