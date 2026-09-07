<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminActivity extends Model
{
    /** Types shown under Patron notifications (patron-initiated). */
    public const TYPE_ROOM_PENDING = 'room.pending';

    public const TYPE_BOOK_RESERVATION_PENDING = 'book_reservation.pending';

    public const TYPE_PATRON_REGISTRATION = 'patron.registration';

    public const TYPE_PATRON_EDIT_REQUEST = 'patron.edit_request';

    public const TYPE_FEEDBACK_SUBMITTED = 'feedback.submitted';

    public const TYPE_SELF_CHECKOUT = 'self_checkout.completed';

    protected $guarded = [];

    /** @return list<string> */
    public static function patronNotificationTypes(): array
    {
        return [
            self::TYPE_ROOM_PENDING,
            self::TYPE_BOOK_RESERVATION_PENDING,
            self::TYPE_PATRON_REGISTRATION,
            self::TYPE_PATRON_EDIT_REQUEST,
            self::TYPE_FEEDBACK_SUBMITTED,
            self::TYPE_SELF_CHECKOUT,
        ];
    }

    public function scopePatronNotifications(Builder $query): Builder
    {
        return $query->whereIn('type', self::patronNotificationTypes());
    }

    public function scopeStaffActivities(Builder $query): Builder
    {
        return $query->whereNotIn('type', self::patronNotificationTypes());
    }

    public function isPatronNotification(): bool
    {
        return in_array($this->type, self::patronNotificationTypes(), true);
    }

    public function typeLabel(): string
    {
        return str_replace(['_', '.'], ' ', (string) $this->type);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
