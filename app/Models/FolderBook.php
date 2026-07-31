<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderBook extends Model
{
    protected $table = 'library_folder_books';

    protected $fillable = [
        'folder_id',
        'book_id',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(FacultyFolder::class, 'folder_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
}
