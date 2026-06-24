<?php

namespace App\Jobs;

use App\Mail\SigningInvitationMail;
use App\Models\Signatory;
use App\Services\SigningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSignatureReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Signatory $signatory) {}

    public function handle(SigningService $signing): void
    {
        if (! $signing->isCurrentTurn($this->signatory)) {
            return;
        }

        Mail::to($this->signatory->email)->queue(new SigningInvitationMail($this->signatory));
    }
}
