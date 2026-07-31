<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\Concerns;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Faculty;
use App\Models\FacultyFolder;

trait FormatsClassroomPayload
{
    private function formatClassroom(Classroom $classroom, bool $includeJoinCode = false): array
    {
        $classroom->loadMissing('faculty');

        $payload = [
            'id' => $classroom->id,
            'name' => $classroom->name,
            'description' => $classroom->description,
            'subject' => $classroom->subject,
            'is_private' => (bool) $classroom->is_private,
            'requires_approval' => (bool) $classroom->requires_approval,
            'member_count' => $classroom->approved_members_count
                ?? $classroom->approvedMembers()->count(),
            'pending_count' => $classroom->pending_members_count
                ?? $classroom->members()->where('status', ClassroomMember::STATUS_PENDING)->count(),
            'folder_count' => $classroom->folders_count
                ?? $classroom->folders()->count(),
            'faculty' => $classroom->faculty ? [
                'id' => $classroom->faculty->id,
                'name' => trim($classroom->faculty->firstname.' '.$classroom->faculty->lastname),
                'employee_id' => $classroom->faculty->employee_id,
                'department' => $classroom->faculty->department,
                'designation' => $classroom->faculty->designation,
            ] : null,
            'created_at' => $classroom->created_at?->toIso8601String(),
        ];

        if ($includeJoinCode) {
            $payload['join_code'] = $classroom->join_code;
        }

        return $payload;
    }

    private function formatFolder(FacultyFolder $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'description' => $folder->description,
            'book_count' => $folder->books_count ?? $folder->books()->count(),
            'classroom_count' => $folder->classrooms_count ?? $folder->classrooms()->count(),
            'created_at' => $folder->created_at?->toIso8601String(),
        ];
    }

    private function formatMember(ClassroomMember $member): array
    {
        $member->loadMissing('student');

        return [
            'id' => $member->id,
            'status' => $member->status,
            'joined_at' => $member->joined_at?->toIso8601String(),
            'student' => $member->student ? [
                'id' => $member->student->id,
                'id_number' => $member->student->id_number,
                'name' => trim($member->student->firstname.' '.$member->student->lastname),
                'firstname' => $member->student->firstname,
                'lastname' => $member->student->lastname,
                'course' => $member->student->course,
                'year' => $member->student->year,
            ] : null,
        ];
    }

    private function formatBookCard(Book $book): array
    {
        $available = $book->availability === Book::AVAILABILITY_AVAILABLE;

        return [
            'id' => $book->id,
            'type' => 'book',
            'title' => $book->title_statement,
            'author' => $book->main_author,
            'publication_year' => $book->pub_year,
            'cover_url' => filled($book->cover_image)
                ? asset('storage/'.$book->cover_image)
                : asset('images/defaultBook.png'),
            'availability' => $available ? 'Available' : ($book->availability ?? 'Unavailable'),
            'copies' => 1,
            'call_number' => $book->call_number,
            'content_type' => $book->content_type,
            'library_name' => $book->library_name,
            'course' => $book->course,
            'section' => $book->section,
        ];
    }

    private function ownedClassroom(Faculty $faculty, int $classroomId): ?Classroom
    {
        return Classroom::query()
            ->where('faculty_id', $faculty->id)
            ->whereKey($classroomId)
            ->first();
    }

    private function ownedFolder(Faculty $faculty, int $folderId): ?FacultyFolder
    {
        return FacultyFolder::query()
            ->where('faculty_id', $faculty->id)
            ->whereKey($folderId)
            ->first();
    }
}
