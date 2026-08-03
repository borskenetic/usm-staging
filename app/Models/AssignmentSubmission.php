<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_COMPLETED = 'completed';

    protected $table = 'library_assignment_submissions';

    protected $fillable = [
        'assignment_id',
        'student_id',
        'status',
        'response_text',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
