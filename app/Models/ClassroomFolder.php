<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomFolder extends Model
{
    protected $table = 'library_classroom_folders';

    protected $fillable = [
        'classroom_id',
        'folder_id',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(FacultyFolder::class, 'folder_id');
    }
}
