<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReservation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'library_book_reservations';

    protected $fillable = [
        'student_id',
        'book_id',
        'held_book_id',
        'status',
        'queue_position',
        'reserved_at',
        'available_at',
        'hold_expires_at',
        'fulfilled_at',
        'fulfilled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'available_at' => 'datetime',
        'hold_expires_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function heldBook(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'held_book_id');
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }
}
