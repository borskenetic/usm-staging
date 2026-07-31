<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\FormatsClassroomPayload;
use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileFaculty;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\FacultyFolder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacultyClassroomController extends Controller
{
    use FormatsClassroomPayload;
    use ResolvesMobileFaculty;

    public function index(Request $request): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $classrooms = Classroom::query()
            ->where('faculty_id', $faculty->id)
            ->withCount([
                'approvedMembers',
                'members as pending_members_count' => fn ($q) => $q->where('status', ClassroomMember::STATUS_PENDING),
                'folders',
            ])
            ->latest()
            ->get()
            ->map(fn (Classroom $classroom) => $this->formatClassroom($classroom, includeJoinCode: true))
            ->values();

        return response()->json([
            'message' => 'Classrooms retrieved.',
            'data' => $classrooms,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'is_private' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
        ]);

        $classroom = Classroom::query()->create([
            'faculty_id' => $faculty->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'join_code' => Classroom::generateJoinCode(),
            'is_private' => $validated['is_private'] ?? true,
            'requires_approval' => $validated['requires_approval'] ?? false,
        ]);

        return response()->json([
            'message' => 'Classroom created.',
            'data' => $this->formatClassroom($classroom->fresh()->loadCount(['approvedMembers', 'folders']), includeJoinCode: true),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $classroom = $this->ownedClassroom($faculty, $id);
        if (! $classroom) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $classroom->load(['folders' => fn ($q) => $q->withCount('books')]);
        $classroom->loadCount([
            'approvedMembers',
            'members as pending_members_count' => fn ($q) => $q->where('status', ClassroomMember::STATUS_PENDING),
            'folders',
        ]);

        $data = $this->formatClassroom($classroom, includeJoinCode: true);
        $data['folders'] = $classroom->folders->map(fn (FacultyFolder $folder) => $this->formatFolder($folder))->values();

        return response()->json([
            'message' => 'Classroom retrieved.',
            'data' => $data,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
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
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'is_private' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
        ]);

        $classroom->update($validated);

        return response()->json([
            'message' => 'Classroom updated.',
            'data' => $this->formatClassroom($classroom->fresh()->loadCount(['approvedMembers', 'folders']), includeJoinCode: true),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $classroom = $this->ownedClassroom($faculty, $id);
        if (! $classroom) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $classroom->delete();

        return response()->json([
            'message' => 'Classroom deleted.',
            'data' => null,
        ]);
    }

    public function members(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $classroom = $this->ownedClassroom($faculty, $id);
        if (! $classroom) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $members = $classroom->members()
            ->with('student')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->latest()
            ->get()
            ->map(fn (ClassroomMember $member) => $this->formatMember($member))
            ->values();

        return response()->json([
            'message' => 'Classroom members retrieved.',
            'data' => $members,
        ]);
    }

    public function approveMember(Request $request, int $id, int $member): JsonResponse
    {
        return $this->setMemberStatus($request, $id, $member, ClassroomMember::STATUS_APPROVED);
    }

    public function rejectMember(Request $request, int $id, int $member): JsonResponse
    {
        return $this->setMemberStatus($request, $id, $member, ClassroomMember::STATUS_REJECTED);
    }

    public function regenerateCode(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $classroom = $this->ownedClassroom($faculty, $id);
        if (! $classroom) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $classroom->update(['join_code' => Classroom::generateJoinCode()]);

        return response()->json([
            'message' => 'Join code regenerated.',
            'data' => $this->formatClassroom($classroom->fresh()->loadCount(['approvedMembers', 'folders']), includeJoinCode: true),
        ]);
    }

    public function shareFolder(Request $request, int $id): JsonResponse
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
            'folder_id' => ['required', 'integer', 'exists:library_folders,id'],
        ]);

        $folder = $this->ownedFolder($faculty, (int) $validated['folder_id']);
        if (! $folder) {
            return response()->json(['message' => 'Folder not found.', 'data' => null], 404);
        }

        $classroom->folders()->syncWithoutDetaching([$folder->id]);

        return response()->json([
            'message' => 'Folder shared to classroom.',
            'data' => $this->formatFolder($folder->loadCount(['books', 'classrooms'])),
        ]);
    }

    public function unshareFolder(Request $request, int $id, int $folderId): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $classroom = $this->ownedClassroom($faculty, $id);
        if (! $classroom) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $folder = $this->ownedFolder($faculty, $folderId);
        if (! $folder) {
            return response()->json(['message' => 'Folder not found.', 'data' => null], 404);
        }

        $classroom->folders()->detach($folder->id);

        return response()->json([
            'message' => 'Folder removed from classroom.',
            'data' => null,
        ]);
    }

    private function setMemberStatus(Request $request, int $classroomId, int $memberId, string $status): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $classroom = $this->ownedClassroom($faculty, $classroomId);
        if (! $classroom) {
            return response()->json(['message' => 'Classroom not found.', 'data' => null], 404);
        }

        $member = ClassroomMember::query()
            ->where('classroom_id', $classroom->id)
            ->whereKey($memberId)
            ->first();

        if (! $member) {
            return response()->json(['message' => 'Member not found.', 'data' => null], 404);
        }

        $member->update([
            'status' => $status,
            'joined_at' => $status === ClassroomMember::STATUS_APPROVED
                ? ($member->joined_at ?? Carbon::now('Asia/Manila'))
                : $member->joined_at,
        ]);

        return response()->json([
            'message' => $status === ClassroomMember::STATUS_APPROVED
                ? 'Student approved.'
                : 'Student rejected.',
            'data' => $this->formatMember($member->fresh('student')),
        ]);
    }
}
