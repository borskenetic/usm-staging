<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\Concerns;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Book;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Faculty;
use App\Models\Student;
use Carbon\Carbon;

trait FormatsAssignmentPayload
{
    use FormatsClassroomPayload;

    private function formatAssignment(
        Assignment $assignment,
        ?AssignmentSubmission $mySubmission = null,
        bool $includeBooks = true,
        bool $includeCounts = true,
    ): array {
        $assignment->loadMissing(['classroom', 'faculty']);

        $payload = [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'instructions' => $assignment->instructions,
            'due_at' => $assignment->due_at?->toIso8601String(),
            'status' => $assignment->status,
            'classroom' => [
                'id' => $assignment->classroom_id,
                'name' => $assignment->classroom?->name,
            ],
            'faculty' => $assignment->faculty ? [
                'id' => $assignment->faculty->id,
                'name' => trim($assignment->faculty->firstname.' '.$assignment->faculty->lastname),
            ] : null,
            'created_at' => $assignment->created_at?->toIso8601String(),
            'updated_at' => $assignment->updated_at?->toIso8601String(),
        ];

        if ($includeBooks) {
            $assignment->loadMissing('books');
            $payload['books'] = $assignment->books
                ->map(fn (Book $book) => $this->formatBookCard($book))
                ->values()
                ->all();
            $payload['book_count'] = count($payload['books']);
        } elseif ($includeCounts) {
            $payload['book_count'] = $assignment->books_count
                ?? $assignment->books()->count();
        }

        if ($includeCounts) {
            $payload['submission_counts'] = [
                'assigned' => $assignment->assigned_submissions_count
                    ?? $assignment->submissions()->where('status', AssignmentSubmission::STATUS_ASSIGNED)->count(),
                'submitted' => $assignment->submitted_submissions_count
                    ?? $assignment->submissions()->where('status', AssignmentSubmission::STATUS_SUBMITTED)->count(),
                'completed' => $assignment->completed_submissions_count
                    ?? $assignment->submissions()->where('status', AssignmentSubmission::STATUS_COMPLETED)->count(),
                'total' => $assignment->submissions_count
                    ?? $assignment->submissions()->count(),
            ];
        }

        if ($mySubmission !== null) {
            $payload['my_submission'] = $this->formatSubmission($mySubmission);
        }

        return $payload;
    }

    private function formatSubmission(AssignmentSubmission $submission): array
    {
        $submission->loadMissing('student');

        return [
            'id' => $submission->id,
            'status' => $submission->status,
            'response_text' => $submission->response_text,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'completed_at' => $submission->completed_at?->toIso8601String(),
            'student' => $submission->student ? [
                'id' => $submission->student->id,
                'id_number' => $submission->student->id_number,
                'name' => trim($submission->student->firstname.' '.$submission->student->lastname),
                'course' => $submission->student->course,
                'year' => $submission->student->year,
            ] : null,
        ];
    }

    private function seedSubmissionsForApprovedMembers(Assignment $assignment): void
    {
        $studentIds = ClassroomMember::query()
            ->where('classroom_id', $assignment->classroom_id)
            ->where('status', ClassroomMember::STATUS_APPROVED)
            ->pluck('student_id');

        $now = Carbon::now();
        foreach ($studentIds as $studentId) {
            AssignmentSubmission::query()->firstOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'student_id' => $studentId,
                ],
                [
                    'status' => AssignmentSubmission::STATUS_ASSIGNED,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function ensureStudentSubmission(Assignment $assignment, Student $student): AssignmentSubmission
    {
        return AssignmentSubmission::query()->firstOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'status' => AssignmentSubmission::STATUS_ASSIGNED,
            ]
        );
    }

    private function ownedAssignment(Faculty $faculty, int $assignmentId): ?Assignment
    {
        return Assignment::query()
            ->where('faculty_id', $faculty->id)
            ->whereKey($assignmentId)
            ->first();
    }

    private function studentCanAccessAssignment(Student $student, Assignment $assignment): bool
    {
        if ($assignment->status === Assignment::STATUS_ARCHIVED) {
            return AssignmentSubmission::query()
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $student->id)
                ->exists();
        }

        return ClassroomMember::query()
            ->where('classroom_id', $assignment->classroom_id)
            ->where('student_id', $student->id)
            ->where('status', ClassroomMember::STATUS_APPROVED)
            ->exists();
    }

    /**
     * @param  list<int|string>  $bookIds
     */
    private function syncAssignmentBooks(Assignment $assignment, array $bookIds): void
    {
        $ids = collect($bookIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $validIds = Book::query()->whereIn('id', $ids)->pluck('id')->all();
        $assignment->books()->sync($validIds);
    }
}
