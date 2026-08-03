<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\FormatsAssignmentPayload;
use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileFaculty;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacultyAssignmentController extends Controller
{
    use FormatsAssignmentPayload;
    use ResolvesMobileFaculty;

    public function index(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $classroom = $this->ownedClassroom($faculty, $id);
        if (! $classroom) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $assignments = Assignment::query()
            ->where('classroom_id', $classroom->id)
            ->where('status', Assignment::STATUS_PUBLISHED)
            ->withCount([
                'books',
                'submissions',
                'submissions as assigned_submissions_count' => fn ($q) => $q->where('status', AssignmentSubmission::STATUS_ASSIGNED),
                'submissions as submitted_submissions_count' => fn ($q) => $q->where('status', AssignmentSubmission::STATUS_SUBMITTED),
                'submissions as completed_submissions_count' => fn ($q) => $q->where('status', AssignmentSubmission::STATUS_COMPLETED),
            ])
            ->latest()
            ->get()
            ->map(fn (Assignment $a) => $this->formatAssignment($a, includeBooks: false))
            ->values();

        return response()->json([
            'message' => 'Assignments retrieved.',
            'data' => $assignments,
        ]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $classroom = $this->ownedClassroom($faculty, $id);
        if (! $classroom) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
            'book_ids' => ['sometimes', 'array'],
            'book_ids.*' => ['integer', 'exists:library_books,id'],
        ]);

        $assignment = DB::transaction(function () use ($faculty, $classroom, $validated) {
            $assignment = Assignment::query()->create([
                'classroom_id' => $classroom->id,
                'faculty_id' => $faculty->id,
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
                'due_at' => isset($validated['due_at'])
                    ? Carbon::parse($validated['due_at'])
                    : null,
                'status' => Assignment::STATUS_PUBLISHED,
            ]);

            if (! empty($validated['book_ids'])) {
                $this->syncAssignmentBooks($assignment, $validated['book_ids']);
            }

            $this->seedSubmissionsForApprovedMembers($assignment);

            return $assignment;
        });

        $assignment->load(['books', 'classroom', 'faculty']);
        $assignment->loadCount([
            'books',
            'submissions',
            'submissions as assigned_submissions_count' => fn ($q) => $q->where('status', AssignmentSubmission::STATUS_ASSIGNED),
            'submissions as submitted_submissions_count' => fn ($q) => $q->where('status', AssignmentSubmission::STATUS_SUBMITTED),
            'submissions as completed_submissions_count' => fn ($q) => $q->where('status', AssignmentSubmission::STATUS_COMPLETED),
        ]);

        return response()->json([
            'message' => 'Assignment created.',
            'data' => $this->formatAssignment($assignment),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $assignment = $this->ownedAssignment($faculty, $id);
        if (! $assignment) {
            return response()->json(['message' => 'Assignment not found.', 'data' => null], 404);
        }

        $assignment->load(['books', 'classroom', 'faculty']);
        $assignment->loadCount([
            'books',
            'submissions',
            'submissions as assigned_submissions_count' => fn ($q) => $q->where('status', AssignmentSubmission::STATUS_ASSIGNED),
            'submissions as submitted_submissions_count' => fn ($q) => $q->where('status', AssignmentSubmission::STATUS_SUBMITTED),
            'submissions as completed_submissions_count' => fn ($q) => $q->where('status', AssignmentSubmission::STATUS_COMPLETED),
        ]);

        return response()->json([
            'message' => 'Assignment retrieved.',
            'data' => $this->formatAssignment($assignment),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $assignment = $this->ownedAssignment($faculty, $id);
        if (! $assignment) {
            return response()->json(['message' => 'Assignment not found.', 'data' => null], 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:published,archived'],
            'book_ids' => ['sometimes', 'array'],
            'book_ids.*' => ['integer', 'exists:library_books,id'],
        ]);

        if (array_key_exists('title', $validated)) {
            $assignment->title = $validated['title'];
        }
        if (array_key_exists('instructions', $validated)) {
            $assignment->instructions = $validated['instructions'];
        }
        if (array_key_exists('due_at', $validated)) {
            $assignment->due_at = $validated['due_at']
                ? Carbon::parse($validated['due_at'])
                : null;
        }
        if (array_key_exists('status', $validated)) {
            $assignment->status = $validated['status'];
        }
        $assignment->save();

        if (array_key_exists('book_ids', $validated)) {
            $this->syncAssignmentBooks($assignment, $validated['book_ids']);
        }

        $assignment->load(['books', 'classroom', 'faculty']);
        $assignment->loadCount(['books', 'submissions']);

        return response()->json([
            'message' => 'Assignment updated.',
            'data' => $this->formatAssignment($assignment),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $assignment = $this->ownedAssignment($faculty, $id);
        if (! $assignment) {
            return response()->json(['message' => 'Assignment not found.', 'data' => null], 404);
        }

        $assignment->status = Assignment::STATUS_ARCHIVED;
        $assignment->save();

        return response()->json([
            'message' => 'Assignment archived.',
            'data' => $this->formatAssignment($assignment->fresh()->load('books'), includeBooks: false),
        ]);
    }

    public function submissions(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $assignment = $this->ownedAssignment($faculty, $id);
        if (! $assignment) {
            return response()->json(['message' => 'Assignment not found.', 'data' => null], 404);
        }

        $this->seedSubmissionsForApprovedMembers($assignment);

        $rows = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->with('student')
            ->orderBy('status')
            ->get()
            ->map(fn (AssignmentSubmission $s) => $this->formatSubmission($s))
            ->values();

        return response()->json([
            'message' => 'Submissions retrieved.',
            'data' => $rows,
        ]);
    }

    public function completeSubmission(Request $request, int $id, int $studentId): JsonResponse
    {
        return $this->setSubmissionStatus(
            $request,
            $id,
            $studentId,
            AssignmentSubmission::STATUS_COMPLETED
        );
    }

    public function reopenSubmission(Request $request, int $id, int $studentId): JsonResponse
    {
        return $this->setSubmissionStatus(
            $request,
            $id,
            $studentId,
            AssignmentSubmission::STATUS_ASSIGNED
        );
    }

    private function setSubmissionStatus(
        Request $request,
        int $assignmentId,
        int $studentId,
        string $status,
    ): JsonResponse {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $assignment = $this->ownedAssignment($faculty, $assignmentId);
        if (! $assignment) {
            return response()->json(['message' => 'Assignment not found.', 'data' => null], 404);
        }

        if (! Student::query()->whereKey($studentId)->exists()) {
            return response()->json(['message' => 'Student not found.', 'data' => null], 404);
        }

        $submission = $this->ensureStudentSubmission($assignment, Student::query()->findOrFail($studentId));
        $submission->status = $status;
        if ($status === AssignmentSubmission::STATUS_COMPLETED) {
            $submission->completed_at = Carbon::now();
        } else {
            $submission->completed_at = null;
            if ($status === AssignmentSubmission::STATUS_ASSIGNED) {
                $submission->submitted_at = null;
                $submission->response_text = null;
            }
        }
        $submission->save();

        return response()->json([
            'message' => 'Submission updated.',
            'data' => $this->formatSubmission($submission->fresh()->load('student')),
        ]);
    }
}
