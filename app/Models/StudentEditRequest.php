<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEditRequest extends Model
{
    protected $table = 'library_student_edit_requests';

    protected $fillable = [
        'student_id',
        'lastname',
        'firstname',
        'middle_initial',
        'birthday',
        'course',
        'year',
        'mobile_number',
        'address',
        'emergency_person',
        'emergency_relationship',
        'emergency_number',
        'emergency_address',
        'profile_picture',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'birthday' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
