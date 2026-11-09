<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BookLog;
use App\Services\CirculationTransactionMailer;

class BookLogObserver
{
    public function __construct(private CirculationTransactionMailer $mailer) {}

    public function created(BookLog $log): void
    {
        $this->mailer->queue($log);
    }

    public function updated(BookLog $log): void
    {
        if ($log->wasChanged('last_renewed_at')) {
            $this->mailer->queue($log, true);
        }
    }
}
