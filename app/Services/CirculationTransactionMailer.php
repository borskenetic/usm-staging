<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\CirculationTransactionMail;
use App\Models\BookLog;
use Illuminate\Support\Facades\DB;
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
            return;
        }

        try {
            Mail::to($email)->send(new CirculationTransactionMail($log, $renewal));
        } catch (Throwable $e) {
            report($e);
        }
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
