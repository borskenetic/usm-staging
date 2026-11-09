<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\BookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CirculationTransactionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookLog $log,
        public bool $renewal = false,
    ) {
        $this->log->loadMissing(['book', 'student']);
    }

    public function kind(): string
    {
        if ($this->renewal) {
            return 'Renewal';
        }

        return $this->log->circulationLabel();
    }

    public function build(): self
    {
        $title = $this->log->book?->title_statement ?: 'a library book';

        return $this->subject($this->kind().': '.$title)
            ->view('emails.circulation.transaction');
    }
}
