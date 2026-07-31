<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacultyFolder extends Model
{
    protected $table = 'library_folders';

    protected $fillable = [
        'faculty_id',
        'name',
        'description',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    public function folderBooks(): HasMany
    {
        return $this->hasMany(FolderBook::class, 'folder_id');
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(
            Book::class,
            'library_folder_books',
            'folder_id',
            'book_id'
        )->withTimestamps();
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(
            Classroom::class,
            'library_classroom_folders',
            'folder_id',
            'classroom_id'
        )->withTimestamps();
    }
}
