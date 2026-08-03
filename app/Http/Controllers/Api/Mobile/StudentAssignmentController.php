<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\FormatsAssignmentPayload;
use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassroomMember;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAssignmentController extends Controller
{
    use FormatsAssignmentPayload;
    use ResolvesMobileStudent;

    public function indexForClassroom(Request $request, int $id): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $member = ClassroomMember::query()
            ->where('classroom_id', $id)
            ->where('student_id', $student->id)
            ->where('status', ClassroomMember::STATUS_APPROVED)
            ->first();

        if (! $member) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $assignments = Assignment::query()
            ->where('classroom_id', $id)
            ->where('status', Assignment::STATUS_PUBLISHED)
            ->with(['books', 'classroom', 'faculty'])
            ->withCount('books')
            ->latest()
            ->get()
            ->map(function (Assignment $assignment) use ($student) {
                $submission = $this->ensureStudentSubmission($assignment, $student);

                return $this->formatAssignment($assignment, $submission, includeCounts: false);
            })
            ->values();

        return response()->json([
            'message' => 'Assignments retrieved.',
            'data' => $assignments,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $classroomIds = ClassroomMember::query()
            ->where('student_id', $student->id)
            ->where('status', ClassroomMember::STATUS_APPROVED)
            ->pluck('classroom_id');

        $assignments = Assignment::query()
            ->whereIn('classroom_id', $classroomIds)
            ->where('status', Assignment::STATUS_PUBLISHED)
            ->with(['books', 'classroom', 'faculty'])
            ->withCount('books')
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->latest()
            ->get()
            ->map(function (Assignment $assignment) use ($student) {
                $submission = $this->ensureStudentSubmission($assignment, $student);

                return $this->formatAssignment($assignment, $submission, includeCounts: false);
            })
            ->values();

        return response()->json([
            'message' => 'Assignments retrieved.',
            'data' => $assignments,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $assignment = Assignment::query()
            ->with(['books', 'classroom', 'faculty'])
            ->whereKey($id)
            ->first();

        if (! $assignment || ! $this->studentCanAccessAssignment($student, $assignment)) {
            return response()->json(['message' => 'Assignment not found.', 'data' => null], 404);
        }

        $submission = $this->ensureStudentSubmission($assignment, $student);

        return response()->json([
            'message' => 'Assignment retrieved.',
            'data' => $this->formatAssignment($assignment, $submission, includeCounts: false),
        ]);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'response_text' => ['nullable', 'string', 'max:10000'],
        ]);

        $assignment = Assignment::query()->whereKey($id)->first();
        if (! $assignment
            || $assignment->status !== Assignment::STATUS_PUBLISHED
            || ! $this->studentCanAccessAssignment($student, $assignment)) {
            return response()->json(['message' => 'Assignment not found.', 'data' => null], 404);
        }

        $submission = $this->ensureStudentSubmission($assignment, $student);
        $text = isset($validated['response_text'])
            ? trim((string) $validated['response_text'])
            : '';

        if ($text === '') {
            $submission->status = AssignmentSubmission::STATUS_COMPLETED;
            $submission->completed_at = Carbon::now();
            $submission->submitted_at = $submission->submitted_at ?? Carbon::now();
        } else {
            $submission->response_text = $text;
            $submission->status = AssignmentSubmission::STATUS_SUBMITTED;
            $submission->submitted_at = Carbon::now();
            $submission->completed_at = null;
        }
        $submission->save();

        $assignment->load(['books', 'classroom', 'faculty']);

        return response()->json([
            'message' => 'Assignment submitted.',
            'data' => $this->formatAssignment($assignment, $submission->fresh(), includeCounts: false),
        ]);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $assignment = Assignment::query()->whereKey($id)->first();
        if (! $assignment
            || $assignment->status !== Assignment::STATUS_PUBLISHED
            || ! $this->studentCanAccessAssignment($student, $assignment)) {
            return response()->json(['message' => 'Assignment not found.', 'data' => null], 404);
        }

        $submission = $this->ensureStudentSubmission($assignment, $student);
        $submission->status = AssignmentSubmission::STATUS_COMPLETED;
        $submission->completed_at = Carbon::now();
        $submission->submitted_at = $submission->submitted_at ?? Carbon::now();
        $submission->save();

        $assignment->load(['books', 'classroom', 'faculty']);

        return response()->json([
            'message' => 'Assignment marked complete.',
            'data' => $this->formatAssignment($assignment, $submission->fresh(), includeCounts: false),
        ]);
    }
}
