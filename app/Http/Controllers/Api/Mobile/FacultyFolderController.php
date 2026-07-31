<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\FormatsClassroomPayload;
use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileFaculty;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\FacultyFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacultyFolderController extends Controller
{
    use FormatsClassroomPayload;
    use ResolvesMobileFaculty;

    public function index(Request $request): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $folders = FacultyFolder::query()
            ->where('faculty_id', $faculty->id)
            ->withCount(['books', 'classrooms'])
            ->latest()
            ->get()
            ->map(fn (FacultyFolder $folder) => $this->formatFolder($folder))
            ->values();

        return response()->json([
            'message' => 'Folders retrieved.',
            'data' => $folders,
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
        ]);

        $folder = FacultyFolder::query()->create([
            'faculty_id' => $faculty->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Folder created.',
            'data' => $this->formatFolder($folder->loadCount(['books', 'classrooms'])),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $folder = $this->ownedFolder($faculty, $id);
        if (! $folder) {
            return response()->json(['message' => 'Folder not found.', 'data' => null], 404);
        }

        $folder->load(['books', 'classrooms']);
        $folder->loadCount(['books', 'classrooms']);

        $data = $this->formatFolder($folder);
        $data['books'] = $folder->books->map(fn (Book $book) => $this->formatBookCard($book))->values();
        $data['classrooms'] = $folder->classrooms->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ])->values();

        return response()->json([
            'message' => 'Folder retrieved.',
            'data' => $data,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $folder = $this->ownedFolder($faculty, $id);
        if (! $folder) {
            return response()->json(['message' => 'Folder not found.', 'data' => null], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $folder->update($validated);

        return response()->json([
            'message' => 'Folder updated.',
            'data' => $this->formatFolder($folder->fresh()->loadCount(['books', 'classrooms'])),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $folder = $this->ownedFolder($faculty, $id);
        if (! $folder) {
            return response()->json(['message' => 'Folder not found.', 'data' => null], 404);
        }

        $folder->delete();

        return response()->json([
            'message' => 'Folder deleted.',
            'data' => null,
        ]);
    }

    public function addBooks(Request $request, int $id): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $folder = $this->ownedFolder($faculty, $id);
        if (! $folder) {
            return response()->json(['message' => 'Folder not found.', 'data' => null], 404);
        }

        $validated = $request->validate([
            'book_ids' => ['required', 'array', 'min:1'],
            'book_ids.*' => ['integer', 'exists:library_books,id'],
        ]);

        $folder->books()->syncWithoutDetaching($validated['book_ids']);

        return response()->json([
            'message' => 'Books added to folder.',
            'data' => $this->formatFolder($folder->fresh()->loadCount(['books', 'classrooms'])),
        ]);
    }

    public function removeBook(Request $request, int $id, int $bookId): JsonResponse
    {
        $faculty = $this->resolveFaculty($request);
        if ($faculty instanceof JsonResponse) {
            return $faculty;
        }

        $folder = $this->ownedFolder($faculty, $id);
        if (! $folder) {
            return response()->json(['message' => 'Folder not found.', 'data' => null], 404);
        }

        $folder->books()->detach($bookId);

        return response()->json([
            'message' => 'Book removed from folder.',
            'data' => $this->formatFolder($folder->fresh()->loadCount(['books', 'classrooms'])),
        ]);
    }
}
