<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReservation extends Model
{
    protected $table = 'library_book_reservations';

    protected $fillable = [
        'student_id',
        'book_id',
        'status',
        'queue_position',
        'reserved_at',
        'available_at',
        'hold_expires_at',
        'cancelled_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'available_at' => 'datetime',
        'hold_expires_at' => 'datetime',
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
}