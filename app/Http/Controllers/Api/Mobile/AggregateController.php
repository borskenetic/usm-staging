<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\BookController;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookLog;
use App\Models\Program;
use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AggregateController extends Controller
{
    use ResolvesMobileStudent;

    public function home(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $activeLoans = $this->activeLoans($student);
        $newArrivals = $this->newArrivals();
        $recommendationContext = $this->recommendationContextForStudent($student);
        $recommendedBooks = $this->recommendationsForStudent($student);
        $facultyRecommendations = app(StudentClassroomController::class)
            ->facultyRecommendationsForStudent($student);

        return $this->etagResponse($request, [
            'message' => 'Mobile home data retrieved.',
            'data' => [
                'new_arrivals' => $newArrivals,
                'active_loans' => $activeLoans,
                'loan_stats' => $this->loanStats($activeLoans),
                'recommended_books' => $recommendedBooks,
                'recommendation_context' => $recommendationContext,
                'faculty_recommendations' => $facultyRecommendations,
            ],
        ]);
    }

    public function borrowOverview(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        BookLog::cachedFineSettings();

        $activeLoans = $this->activeLoans($student);
        $currentLoans = count($activeLoans);
        $hasOverdue = collect($activeLoans)->where('is_overdue', true)->isNotEmpty();
        $outstandingFinesTotal = round(
            collect($activeLoans)->sum(fn (array $loan) => (float) ($loan['fine'] ?? 0)),
            2,
        );

        $history = BookLog::query()
            ->with('book:id,title_statement,main_author,call_number,accession_no,barcode')
            ->where('student_id', $student->id)
            ->latest('timestamp')
            ->paginate(10)
            ->withQueryString();

        return $this->etagResponse($request, [
            'message' => 'Borrow overview retrieved.',
            'data' => [
                'active_loans' => $activeLoans,
                'history' => $history->getCollection()->map(fn (BookLog $log) => $this->formatLoan($log))->values(),
                'history_meta' => $this->paginationMeta($history),
                'outstanding_fines_total' => $outstandingFinesTotal,
                'limits' => $this->borrowLimitsFromCache($student, $currentLoans, $hasOverdue),
            ],
        ]);
    }

    public function roomsDashboard(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'room_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date'],
        ]);

        $rooms = Cache::remember('mobile:rooms', now()->addMinutes(10), function () {
            return Room::query()
                ->orderBy('name')
                ->get();
        });

        if (isset($validated['room_id']) && ! $rooms->contains('id', (int) $validated['room_id'])) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['room_id' => ['The selected room id is invalid.']],
            ], 422);
        }

        $selectedRoom = $rooms->firstWhere('id', (int) ($validated['room_id'] ?? 0)) ?? $rooms->first();
        $date = Carbon::parse($validated['date'] ?? Carbon::now('Asia/Manila'))->toDateString();

        $reservations = RoomReservation::query()
            ->with(['room', 'students'])
            ->where('student_id', $student->id)
            ->latest('date')
            ->latest('start_time')
            ->get()
            ->map(fn (RoomReservation $reservation) => $this->formatReservation($reservation))
            ->values();

        $bookedSlots = collect();

        if ($selectedRoom instanceof Room) {
            $bookedSlots = RoomReservation::query()
                ->select('id', 'start_time', 'end_time', 'status')
                ->where('room_id', $selectedRoom->id)
                ->whereDate('date', $date)
                ->whereIn('status', ['pending', 'approved'])
                ->orderBy('start_time')
                ->get()
                ->map(fn (RoomReservation $reservation) => [
                    'reservation_id' => $reservation->id,
                    'start_time' => $this->formatTime((string) $reservation->start_time),
                    'end_time' => $this->formatTime((string) $reservation->end_time),
                    'status' => $reservation->status,
                ])
                ->values();
        }

        return $this->etagResponse($request, [
            'message' => 'Room dashboard retrieved.',
            'data' => [
                'current_user' => [
                    'user' => $this->formatUser($student),
                    'student' => $this->formatStudent($student),
                ],
                'rooms' => $rooms->map(fn (Room $room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'capacity' => $room->capacity,
                ])->values(),
                'reservations' => $reservations,
                'availability' => [
                    'room_id' => $selectedRoom?->id,
                    'date' => $date,
                    'booked_slots' => $bookedSlots,
                ],
            ],
        ]);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        return $this->etagResponse($request, [
            'message' => 'Recommendations retrieved.',
            'data' => $this->recommendationsForStudent($student),
        ]);
    }

    private function newArrivals(): array
    {
        return Cache::remember('mobile:new-arrivals', now()->addMinutes(5), function () {
            $grouped = Book::query()
                ->whereNull('archived_at')
                ->select(
                    'title_statement',
                    'main_author',
                    'pub_year',
                    DB::raw('COUNT(*) AS copies'),
                    DB::raw('MIN(id) AS sample_id'),
                    DB::raw("MAX(CASE WHEN availability = 'Available' THEN 1 ELSE 0 END) AS is_available"),
                    DB::raw('MAX(created_at) AS newest_copy_at')
                )
                ->groupBy('title_statement', 'main_author', 'pub_year');

            return DB::query()
                ->fromSub($grouped, 'grouped')
                ->join('library_books', 'library_books.id', '=', 'grouped.sample_id')
                ->select(
                    'grouped.title_statement',
                    'grouped.main_author',
                    'grouped.pub_year',
                    'grouped.copies',
                    'grouped.sample_id as id',
                    'grouped.is_available',
                    'library_books.call_number',
                    'library_books.cover_image',
                    'library_books.content_type',
                    'library_books.library_name',
                    'library_books.course',
                    'library_books.section'
                )
                ->orderByDesc('grouped.newest_copy_at')
                ->limit(10)
                ->get()
                ->map(fn (object $book) => $this->formatBookSearchRow($book))
                ->values()
                ->all();
        });
    }

    private function activeLoans(Student $student): array
    {
        return $this->activeLoanQuery($student)
            ->with('book:id,title_statement,main_author,call_number,accession_no,barcode')
            ->orderBy('due_date')
            ->get()
            ->map(fn (BookLog $log) => $this->formatLoan($log))
            ->values()
            ->all();
    }

    private function activeLoanQuery(Student $student)
    {
        $latestIds = DB::table('library_book_logs')
            ->select(DB::raw('MAX(id) as id'))
            ->where('student_id', $student->id)
            ->groupBy('book_id');

        return BookLog::query()
            ->whereIn('id', $latestIds)
            ->where('status', 'Checked Out');
    }

    private function loanStats(array $activeLoans): array
    {
        $today = Carbon::now('Asia/Manila')->startOfDay();

        return [
            'active_count' => count($activeLoans),
            'due_soon_count' => collect($activeLoans)->filter(function (array $loan) use ($today): bool {
                if ($loan['is_overdue'] || empty($loan['due_at'])) {
                    return false;
                }

                $dueDate = Carbon::parse($loan['due_at'], 'Asia/Manila')->startOfDay();

                return $dueDate->gte($today) && $today->diffInDays($dueDate) <= 3;
            })->count(),
            'overdue_count' => collect($activeLoans)->where('is_overdue', true)->count(),
        ];
    }

    private function borrowLimitsFromCache(Student $student, int $currentLoans, bool $hasOverdue): array
    {
        $maxLoans = BookController::MAX_CONCURRENT_BOOK_LOANS_PER_STUDENT;
        $fineSetting = BookLog::cachedFineSettings();

        return [
            'max_active_loans' => $maxLoans,
            'current_active_loans' => $currentLoans,
            'remaining_loans' => max(0, $maxLoans - $currentLoans),
            'has_overdue' => $hasOverdue,
            'can_borrow' => $currentLoans < $maxLoans && ! $hasOverdue,
            'reborrow_cooldown_days' => BookController::REBORROW_COOLDOWN_DAYS,
            'fine_settings_configured' => $fineSetting->exists,
            'loan_duration_days' => $fineSetting->loan_duration_days,
            'grace_period_days' => $fineSetting->grace_period_days,
        ];
    }

    private function recommendationContextForStudent(Student $student): array
    {
        $course = trim((string) $student->course);

        if ($course === '') {
            return [
                'course' => null,
                'program_name' => null,
            ];
        }

        $program = $this->resolveProgramForCourse($course);

        return [
            'course' => $course,
            'program_name' => $program?->program_name,
        ];
    }

    private function resolveProgramForCourse(string $course): ?Program
    {
        $normalizedCourse = mb_strtolower($course);

        return Program::query()
            ->where(function ($query) use ($course, $normalizedCourse) {
                $query->whereRaw('LOWER(program_code) = ?', [$normalizedCourse])
                    ->orWhereRaw('LOWER(program_name) = ?', [$normalizedCourse])
                    ->orWhere('program_code', $course)
                    ->orWhere('program_name', $course);
            })
            ->first();
    }

    private function recommendationsForStudent(Student $student): array
    {
        $course = trim((string) $student->course);
        $normalizedCourse = mb_strtolower($course);
        $cacheKey = 'mobile:recommendations:'.$student->id.':'.$normalizedCourse;

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($student, $course) {
            if ($course === '') {
                return [];
            }

            $program = $this->resolveProgramForCourse($course);
            $programIds = $program ? [$program->id] : [];

            if ($programIds === [] && ! Schema::hasColumn('library_books', 'program')) {
                return [];
            }

            $grouped = Book::query()
                ->whereNull('archived_at')
                ->where(function ($query) use ($programIds, $course) {
                    if ($programIds !== []) {
                        $query->whereHas('programs', function ($programQuery) use ($programIds) {
                            $programQuery->whereIn('library_programs.id', $programIds);
                        });
                    }

                    if (Schema::hasColumn('library_books', 'program')) {
                        $query->orWhere('program', $course);
                    }
                })
                ->select(
                    'title_statement',
                    'main_author',
                    'pub_year',
                    DB::raw('COUNT(*) AS copies'),
                    DB::raw('MIN(id) AS sample_id'),
                    DB::raw("MAX(CASE WHEN availability = 'Available' THEN 1 ELSE 0 END) AS is_available"),
                    DB::raw('MAX(created_at) AS newest_copy_at')
                )
                ->groupBy('title_statement', 'main_author', 'pub_year');

            $books = DB::query()
                ->fromSub($grouped, 'grouped')
                ->join('library_books', 'library_books.id', '=', 'grouped.sample_id')
                ->select(
                    'grouped.title_statement',
                    'grouped.main_author',
                    'grouped.pub_year',
                    'grouped.copies',
                    'grouped.sample_id as id',
                    'grouped.is_available',
                    'library_books.call_number',
                    'library_books.cover_image',
                    'library_books.content_type',
                    'library_books.library_name',
                    'library_books.course',
                    'library_books.section'
                )
                ->orderByDesc('grouped.is_available')
                ->orderByDesc('grouped.newest_copy_at')
                ->limit(10)
                ->get()
                ->map(fn (object $book) => $this->formatBookSearchRow($book))
                ->values()
                ->all();

            return $books;
        });
    }

    private function hasOverdueLoans(Student $student): bool
    {
        return $this->activeLoanQuery($student)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', Carbon::now('Asia/Manila')->toDateString())
            ->exists();
    }

    private function formatBookSearchRow(object $book): array
    {
        return [
            'id' => (int) $book->id,
            'type' => 'book',
            'title' => $book->title_statement,
            'author' => $book->main_author,
            'publication_year' => $book->pub_year,
            'cover_url' => filled($book->cover_image) ? asset('storage/'.$book->cover_image) : asset('images/defaultBook.png'),
            'availability' => (int) $book->is_available === 1 ? 'Available' : 'Unavailable',
            'copies' => (int) $book->copies,
            'call_number' => $book->call_number,
            'content_type' => $book->content_type,
            'library_name' => $book->library_name,
            'course' => $book->course,
            'section' => $book->section,
        ];
    }

    private function formatLoan(BookLog $log): array
    {
        $book = $log->book;

        return [
            'id' => $log->id,
            'book_id' => $log->book_id,
            'title' => $book?->title_statement,
            'author' => $book?->main_author,
            'call_number' => $book?->call_number,
            'accession_no' => $book?->accession_no,
            'barcode' => $book?->barcode,
            'borrowed_at' => $log->timestamp?->timezone('Asia/Manila')->toDateTimeString(),
            'due_at' => $log->due_date?->format('Y-m-d'),
            'returned_at' => $log->returned_date?->timezone('Asia/Manila')->toDateTimeString(),
            'status' => $log->status,
            'circulation_type' => $log->circulation_type,
            'is_overdue' => (bool) $log->is_overdue,
            'days_overdue' => (int) $log->days_overdue,
            'fine' => (float) $log->total_fine,
            'renew_count' => (int) $log->renew_count,
        ];
    }

    private function formatReservation(RoomReservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'room' => [
                'id' => $reservation->room?->id,
                'name' => $reservation->room?->name,
                'capacity' => $reservation->room?->capacity,
            ],
            'date' => Carbon::parse($reservation->date)->toDateString(),
            'start_time' => $this->formatTime((string) $reservation->start_time),
            'end_time' => $this->formatTime((string) $reservation->end_time),
            'status' => $reservation->status,
            'patron_email' => $reservation->patron_email,
            'number_of_students' => $reservation->number_of_students,
            'student_names' => $reservation->students->pluck('name')->values(),
            'notes' => $reservation->notes,
            'approved_at' => $reservation->approved_at ? Carbon::parse($reservation->approved_at)->toDateTimeString() : null,
            'created_at' => $reservation->created_at?->toDateTimeString(),
        ];
    }

    private function formatUser(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => trim((string) $student->firstname.' '.(string) $student->lastname),
            'fname' => $student->firstname,
            'lname' => $student->lastname,
            'email' => null,
            'role' => 'student',
        ];
    }

    private function formatStudent(Student $student): array
    {
        return [
            'id' => $student->id,
            'id_number' => $student->id_number,
            'lastname' => $student->lastname,
            'firstname' => $student->firstname,
            'middle_initial' => $student->middle_initial,
            'course' => $student->course,
            'year' => $student->year,
            'mobile_number' => $student->mobile_number,
            'address' => $student->address,
        ];
    }

    private function formatTime(string $time): string
    {
        return Carbon::parse($time, 'Asia/Manila')->format('H:i:s');
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    private function etagResponse(Request $request, array $payload): JsonResponse
    {
        $etag = '"'.sha1(json_encode($payload, JSON_THROW_ON_ERROR)).'"';

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->json(null, 304)->header('ETag', $etag);
        }

        return response()->json($payload)->header('ETag', $etag);
    }
}