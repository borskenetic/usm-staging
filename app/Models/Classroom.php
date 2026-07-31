<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Classroom extends Model
{
    protected $table = 'library_classrooms';

    protected $fillable = [
        'faculty_id',
        'name',
        'description',
        'subject',
        'join_code',
        'is_private',
        'requires_approval',
    ];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'requires_approval' => 'boolean',
        ];
    }

    public static function generateJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::query()->where('join_code', $code)->exists());

        return $code;
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClassroomMember::class, 'classroom_id');
    }

    public function approvedMembers(): HasMany
    {
        return $this->members()->where('status', ClassroomMember::STATUS_APPROVED);
    }

    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(
            FacultyFolder::class,
            'library_classroom_folders',
            'classroom_id',
            'folder_id'
        )->withTimestamps();
    }

    public function classroomFolders(): HasMany
    {
        return $this->hasMany(ClassroomFolder::class, 'classroom_id');
    }
}
