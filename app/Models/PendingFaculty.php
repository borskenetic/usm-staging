<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingFaculty extends Model
{
    protected $table = 'library_pending_faculty';

    protected $fillable = [
        'employee_id',
        'firstname',
        'lastname',
        'middle_initial',
        'email',
        'department',
        'designation',
        'birthday',
        'mobile_number',
        'address',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_number',
        'emergency_address',
        'profile_picture',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
        ];
    }
}
