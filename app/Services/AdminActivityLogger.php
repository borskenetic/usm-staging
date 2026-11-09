<?php

namespace App\Services;

use App\Models\AdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AdminActivityLogger
{
    public function log(
        string $module,
        string $type,
        string $title,
        ?string $body = null,
        ?Model $subject = null,
        ?string $actionUrl = null,
        ?string $icon = null,
    ): ?AdminActivity {
        if (! Schema::hasTable('admin_activities')) {
            return null;
        }

        $activity = AdminActivity::query()->create([
            'user_id' => Auth::id(),
            'module' => $module,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'icon' => $icon,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
        ]);

        Cache::increment('topbar.notifications.version');

        return $activity;
    }

    public function patronRegistration(string $kind, string $name, string $idNumber): ?AdminActivity
    {
        return $this->log(
            'library',
            AdminActivity::TYPE_PATRON_REGISTRATION,
            'New '.$kind.' registration',
            "{$name} ({$idNumber}) awaiting approval",
            null,
            route('pending.index'),
            'bi-person-plus',
        );
    }

    public function patronEditRequest(Model $editRequest, string $studentLabel): ?AdminActivity
    {
        return $this->log(
            'library',
            AdminActivity::TYPE_PATRON_EDIT_REQUEST,
            'Patron profile edit request',
            "{$studentLabel} submitted changes for review",
            $editRequest,
            route('students.pending.requests'),
            'bi-pencil-square',
        );
    }

    public function feedbackSubmitted(string $messagePreview, ?Model $feedback = null): ?AdminActivity
    {
        return $this->log(
            'library',
            AdminActivity::TYPE_FEEDBACK_SUBMITTED,
            'New OPAC feedback',
            $messagePreview,
            $feedback,
            route('feedback.index'),
            'bi-chat-left-text',
        );
    }

    public function roomReservationPending(Model $reservation, string $roomName, string $date): ?AdminActivity
    {
        return $this->log(
            'library',
            AdminActivity::TYPE_ROOM_PENDING,
            'New room reservation',
            "{$roomName} on {$date} — pending approval",
            $reservation,
            route('rooms.pending'),
            'bi-calendar-plus',
        );
    }

    public function bookReservationPending(Model $reservation, string $studentLabel, string $bookTitle): ?AdminActivity
    {
        return $this->log(
            'library',
            AdminActivity::TYPE_BOOK_RESERVATION_PENDING,
            'OPAC book reservation',
            "{$studentLabel} reserved «{$bookTitle}»",
            $reservation,
            route('books.reservations.pending'),
            'bi-bookmark-plus',
        );
    }

    /**
     * @param  list<string>  $bookTitles
     */
    public function selfCheckout(string $studentLabel, int $bookCount, array $bookTitles = []): ?AdminActivity
    {
        $bookWord = $bookCount === 1 ? 'book' : 'books';
        $titles = collect($bookTitles)
            ->map(fn ($title) => trim((string) $title))
            ->filter()
            ->take(5)
            ->implode('; ');
        $body = "{$studentLabel} started room use for {$bookCount} {$bookWord}";
        if ($titles !== '') {
            $body .= ': '.$titles;
        }

        return $this->log(
            'library',
            AdminActivity::TYPE_SELF_CHECKOUT,
            'Kiosk room use',
            $body,
            null,
            route('logs.index', ['circulation_type' => 'room_use']),
            'bi-book',
        );
    }
}
