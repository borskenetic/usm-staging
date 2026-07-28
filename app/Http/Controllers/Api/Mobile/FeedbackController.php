<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'comments' => ['required', 'string', 'max:5000'],
        ]);

        [$name, $email, $studentId] = $this->feedbackIdentity($request);
        $category = $this->resolveCategory(
            $validated['category'] ?? null,
            $validated['comments']
        );

        $feedback = Feedback::query()->create([
            'name' => $name !== '' ? $name : null,
            'email' => $email,
            'category' => $category,
            'source' => 'mobile',
            'student_id' => $studentId,
            'comments' => $validated['comments'],
        ]);

        return response()->json([
            'message' => 'Feedback submitted successfully.',
            'data' => [
                'id' => $feedback->id,
                'name' => $feedback->name,
                'email' => $feedback->email,
                'category' => $feedback->category,
                'source' => $feedback->source,
                'comments' => $feedback->comments,
                'created_at' => $feedback->created_at?->toDateTimeString(),
            ],
        ], 201);
    }

    private function feedbackIdentity(Request $request): array
    {
        $tokenable = $request->user();

        if ($tokenable instanceof Student) {
            return [
                trim((string) $tokenable->firstname.' '.(string) $tokenable->lastname),
                null,
                $tokenable->id,
            ];
        }

        if ($tokenable instanceof User) {
            $tokenable->loadMissing('student');

            $student = $tokenable->student;
            $name = trim((string) $tokenable->fname.' '.(string) $tokenable->lname);

            if ($name === '' && $student) {
                $name = trim((string) $student->firstname.' '.(string) $student->lastname);
            }

            return [$name, $tokenable->email, $student?->id];
        }

        return ['', null, null];
    }

    private function resolveCategory(?string $category, string $comments): ?string
    {
        $category = trim((string) $category);
        if ($category !== '') {
            return $category;
        }

        if (preg_match('/^\[(.+?)\]\s*/', $comments, $matches) === 1) {
            return trim($matches[1]) !== '' ? trim($matches[1]) : null;
        }

        return null;
    }
}
