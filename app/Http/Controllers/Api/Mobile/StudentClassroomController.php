<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\FormatsClassroomPayload;
use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\FacultyFolder;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StudentClassroomController extends Controller
{
    use FormatsClassroomPayload;
    use ResolvesMobileStudent;

    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $memberships = ClassroomMember::query()
            ->with(['classroom.faculty'])
            ->where('student_id', $student->id)
            ->where('status', ClassroomMember::STATUS_APPROVED)
            ->latest('joined_at')
            ->get();

        $data = $memberships->map(function (ClassroomMember $membership) {
            $classroom = $membership->classroom;
            if (! $classroom) {
                return null;
            }

            $payload = $this->formatClassroom($classroom);
            $payload['membership_status'] = $membership->status;
            $payload['joined_at'] = $membership->joined_at?->toIso8601String();

            return $payload;
        })->filter()->values();

        return response()->json([
            'message' => 'Classrooms retrieved.',
            'data' => $data,
        ]);
    }

    public function join(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'join_code' => ['required', 'string', 'max:32'],
        ]);

        $code = strtoupper(trim($validated['join_code']));
        $classroom = Classroom::query()->where('join_code', $code)->first();

        if (! $classroom) {
            return response()->json([
                'message' => 'Invalid classroom join code.',
                'data' => null,
            ], 404);
        }

        $existing = ClassroomMember::query()
            ->where('classroom_id', $classroom->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && $existing->status === ClassroomMember::STATUS_APPROVED) {
            return response()->json([
                'message' => 'You are already a member of this classroom.',
                'data' => $this->formatClassroom($classroom),
            ]);
        }

        $status = $classroom->requires_approval
            ? ClassroomMember::STATUS_PENDING
            : ClassroomMember::STATUS_APPROVED;

        $member = ClassroomMember::query()->updateOrCreate(
            [
                'classroom_id' => $classroom->id,
                'student_id' => $student->id,
            ],
            [
                'status' => $status,
                'joined_at' => $status === ClassroomMember::STATUS_APPROVED
                    ? Carbon::now('Asia/Manila')
                    : null,
            ]
        );

        return response()->json([
            'message' => $status === ClassroomMember::STATUS_APPROVED
                ? 'Joined classroom successfully.'
                : 'Join request submitted. Waiting for faculty approval.',
            'data' => [
                'membership' => $this->formatMember($member->load('student')),
                'classroom' => $this->formatClassroom($classroom),
            ],
        ], $existing ? 200 : 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $membership = ClassroomMember::query()
            ->where('student_id', $student->id)
            ->where('classroom_id', $id)
            ->where('status', ClassroomMember::STATUS_APPROVED)
            ->first();

        if (! $membership) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $classroom = Classroom::query()
            ->with(['faculty', 'folders.books'])
            ->findOrFail($id);

        $books = $this->uniqueBooksFromFolders($classroom->folders);

        $data = $this->formatClassroom($classroom);
        $data['membership_status'] = $membership->status;
        $data['books'] = $books->map(fn (Book $book) => $this->formatBookCard($book))->values();
        $data['folders'] = $classroom->folders->map(fn (FacultyFolder $folder) => $this->formatFolder($folder))->values();

        return response()->json([
            'message' => 'Classroom retrieved.',
            'data' => $data,
        ]);
    }

    public function leave(Request $request, int $id): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $member = ClassroomMember::query()
            ->where('student_id', $student->id)
            ->where('classroom_id', $id)
            ->first();

        if (! $member) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $member->update([
            'status' => ClassroomMember::STATUS_LEFT,
            'joined_at' => null,
        ]);

        return response()->json([
            'message' => 'Left classroom.',
            'data' => null,
        ]);
    }

    public function facultyRecommendations(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        return response()->json([
            'message' => 'Faculty recommendations retrieved.',
            'data' => $this->facultyRecommendationsForStudent($student),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function facultyRecommendationsForStudent(Student $student): array
    {
        $classroomIds = ClassroomMember::query()
            ->where('student_id', $student->id)
            ->where('status', ClassroomMember::STATUS_APPROVED)
            ->pluck('classroom_id');

        if ($classroomIds->isEmpty()) {
            return [];
        }

        $classrooms = Classroom::query()
            ->with(['faculty', 'folders.books'])
            ->whereIn('id', $classroomIds)
            ->orderBy('name')
            ->get();

        return $classrooms
            ->map(function (Classroom $classroom) {
                $books = $this->uniqueBooksFromFolders($classroom->folders);
                if ($books->isEmpty()) {
                    return null;
                }

                return [
                    'classroom' => $this->formatClassroom($classroom),
                    'books' => $books->map(fn (Book $book) => $this->formatBookCard($book))->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, FacultyFolder>  $folders
     * @return Collection<int, Book>
     */
    private function uniqueBooksFromFolders(Collection $folders): Collection
    {
        return $folders
            ->flatMap(fn (FacultyFolder $folder) => $folder->books)
            ->unique('id')
            ->values();
    }
}
