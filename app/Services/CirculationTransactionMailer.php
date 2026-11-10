<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\CirculationTransactionMail;
use App\Models\BookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CirculationTransactionMailer
{
    public function queue(BookLog $log, bool $renewal = false): void
    {
        $id = $log->getKey();

        DB::afterCommit(function () use ($id, $renewal): void {
            $fresh = BookLog::query()->with(['book', 'student'])->find($id);
            if ($fresh) {
                $this->send($fresh, $renewal);
            }
        });
    }

    public function send(BookLog $log, bool $renewal = false): void
    {
        $email = $this->recipient($log);
        if ($email === null) {
            $this->notice('Transaction saved. No email was sent because this patron has no email address.');

            return;
        }

        try {
            Mail::to($email)->send(new CirculationTransactionMail($log, $renewal));
            $this->notice('A copy was emailed to '.$email.'.');
        } catch (Throwable $e) {
            report($e);
            Log::warning('Circulation email failed', [
                'book_log_id' => $log->getKey(),
                'to' => $email,
                'error' => $e->getMessage(),
            ]);
            $this->notice('Transaction saved, but the email to '.$email.' could not be sent. Check the mail settings.');
        }
    }

    private function notice(string $message): void
    {
        if (! app()->bound('request') || ! request()->hasSession()) {
            return;
        }

        session()->flash('circulation_mail', $message);
    }

    public function recipient(BookLog $log): ?string
    {
        $log->loadMissing('student');
        $email = trim((string) ($log->student?->email ?? ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }
}
