<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Ebook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'view' => ['nullable', 'in:books,ebooks'],
            'course' => ['nullable', 'string', 'max:255'],
            'content_type' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'subject_topic' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $view = $validated['view'] ?? 'books';
        $perPage = (int) ($validated['per_page'] ?? 10);

        if ($view === 'ebooks') {
            $ebooks = $this->ebookSearchQuery($validated)
                ->orderBy('title')
                ->paginate($perPage)
                ->withQueryString();

            return response()->json([
                'message' => 'Catalog results retrieved.',
                'data' => $ebooks->getCollection()->map(fn (Ebook $ebook) => $this->formatEbook($ebook))->values(),
                'meta' => $this->paginationMeta($ebooks),
            ]);
        }

        $books = $this->bookSearchPaginator($validated, $perPage);

        return response()->json([
            'message' => 'Catalog results retrieved.',
            'data' => $books->getCollection()->map(fn ($book) => $this->formatBookSearchRow($book))->values(),
            'meta' => $this->paginationMeta($books),
        ]);
    }

    public function book(Book $book): JsonResponse
    {
        abort_if($book->archived_at !== null, 404);

        $copies = Book::query()
            ->whereNull('archived_at')
            ->where('title_statement', $book->title_statement)
            ->where('main_author', $book->main_author)
            ->where('pub_year', $book->pub_year)
            ->orderBy('accession_no')
            ->get();

        return response()->json([
            'message' => 'Book details retrieved.',
            'data' => [
                'group' => [
                    'title' => $book->title_statement,
                    'author' => $book->main_author,
                    'publication_year' => $book->pub_year,
                    'copies' => $copies->count(),
                    'availability' => $copies->contains(fn (Book $copy) => $copy->availability === 'Available')
                        ? 'Available'
                        : 'Unavailable',
                ],
                'description' => $this->formatBookDescription($book),
                'copies' => $copies->map(fn (Book $copy) => $this->formatBookCopy($copy))->values(),
            ],
        ]);
    }

    public function ebook(Ebook $ebook): JsonResponse
    {
        $ebook->loadMissing(['program', 'course']);

        return response()->json([
            'message' => 'E-book details retrieved.',
            'data' => $this->formatEbook($ebook),
        ]);
    }

    public function filters(): JsonResponse
    {
        return response()->json([
            'message' => 'Catalog filters retrieved.',
            'data' => Cache::remember('mobile:catalog-filters', now()->addMinutes(15), function () {
                return [
                    'courses' => $this->distinctBookValues('course'),
                    'content_types' => $this->distinctBookValues('content_type'),
                    'sections' => $this->distinctBookValues('section'),
                    'subject_topics' => $this->distinctBookValues('subject_topic'),
                    'genres' => $this->distinctBookValues('genre'),
                ];
            }),
        ]);
    }

    public function newArrivals(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = (int) ($validated['limit'] ?? 12);

        $books = $this->bookSearchPaginator([], $limit, true);

        return response()->json([
            'message' => 'New arrivals retrieved.',
            'data' => $books->getCollection()->map(fn ($book) => $this->formatBookSearchRow($book))->values(),
        ]);
    }

    private function bookSearchPaginator(array $filters, int $perPage, bool $newestFirst = false): LengthAwarePaginator
    {
        $query = Book::query()->whereNull('archived_at');

        $this->applyBookFilters($query, $filters);
        $this->applyBookSearch($query, $filters['search'] ?? null);

        $grouped = $query
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
                'grouped.newest_copy_at',
                'library_books.call_number',
                'library_books.cover_image',
                'library_books.content_type',
                'library_books.library_name',
                'library_books.course',
                'library_books.section'
            )
            ->when(
                $newestFirst,
                fn ($query) => $query->orderByDesc('grouped.newest_copy_at'),
                fn ($query) => $query->orderBy('grouped.title_statement')
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    private function ebookSearchQuery(array $filters): Builder
    {
        $query = Ebook::query()->with(['program', 'course']);
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $like = "%{$search}%";

                $builder->where('title', 'like', $like)
                    ->orWhere('author', 'like', $like)
                    ->orWhere('publisher', 'like', $like)
                    ->orWhere('source', 'like', $like)
                    ->orWhere('publication_year', 'like', $like);
            });
        }

        return $query;
    }

    private function applyBookFilters(Builder $query, array $filters): void
    {
        foreach (['course', 'subject_topic', 'genre', 'content_type', 'section'] as $field) {
            $value = $filters[$field] ?? null;

            if ($value !== null && $value !== '' && ! in_array($value, ['all', 'All'], true)) {
                $query->where($field, $value);
            }
        }
    }

    private function applyBookSearch(Builder $query, ?string $search): void
    {
        $search = is_string($search) ? trim($search) : '';

        if ($search === '') {
            return;
        }

        $tokens = preg_split('/\s+/', $search) ?: [];

        foreach (array_filter(array_map('trim', $tokens)) as $token) {
            $like = "%{$token}%";

            $query->where(function (Builder $builder) use ($like, $token) {
                $builder->where('title_statement', 'like', $like)
                    ->orWhere('main_author', 'like', $like)
                    ->orWhere('title_author', 'like', $like)
                    ->orWhere('control_no', 'like', $like)
                    ->orWhere('isbn', 'like', $like)
                    ->orWhere('publisher', 'like', $like)
                    ->orWhere('pub_place', 'like', $like)
                    ->orWhere('pub_year', 'like', $like)
                    ->orWhere('edition', 'like', $like)
                    ->orWhere('call_number', 'like', $like)
                    ->orWhere('accession_no', 'like', $like)
                    ->orWhere('barcode', 'like', $like)
                    ->orWhere('rfid', 'like', $like)
                    ->orWhere('availability', 'like', $like)
                    ->orWhere('content_type', 'like', $like)
                    ->orWhere('media_type', 'like', $like)
                    ->orWhere('carrier_type', 'like', $like)
                    ->orWhere('library_name', 'like', $like)
                    ->orWhere('section', 'like', $like)
                    ->orWhere('course', 'like', $like)
                    ->orWhere('curriculum', 'like', $like)
                    ->orWhere('year', 'like', $like)
                    ->orWhere('series_title', 'like', $like)
                    ->orWhere('subject_topic', 'like', $like)
                    ->orWhere('subject_form', 'like', $like)
                    ->orWhere('genre', 'like', $like)
                    ->orWhere('general_note', 'like', $like)
                    ->orWhere('bibliography_note', 'like', $like)
                    ->orWhere('source_vendor', 'like', $like)
                    ->orWhere('source_date', 'like', $like)
                    ->orWhereHas('programs', function (Builder $program) use ($token) {
                        $program->where('library_programs.program_name', 'like', "%{$token}%")
                            ->orWhere('library_programs.program_code', 'like', "%{$token}%");
                    });
            });
        }
    }

    private function formatBookSearchRow(object $book): array
    {
        return [
            'id' => (int) $book->id,
            'type' => 'book',
            'title' => $book->title_statement,
            'author' => $book->main_author,
            'publication_year' => $book->pub_year,
            'cover_url' => $this->bookCoverUrl($book->cover_image),
            'availability' => (int) $book->is_available === 1 ? 'Available' : 'Unavailable',
            'copies' => (int) $book->copies,
            'call_number' => $book->call_number,
            'content_type' => $book->content_type,
            'library_name' => $book->library_name,
            'course' => $book->course,
            'section' => $book->section,
        ];
    }

    private function formatBookDescription(Book $book): array
    {
        $published = trim(implode(' ', array_filter([
            $book->pub_place,
            $book->publisher,
            filled($book->pub_year) ? (string) $book->pub_year : null,
        ])));

        $physical = trim(implode(' ', array_filter([
            filled($book->pages) ? trim((string) $book->pages).' p.' : null,
            $book->illustrations,
            filled($book->size) ? trim((string) $book->size).' cm' : null,
        ])));

        return [
            'id' => $book->id,
            'title' => $book->title_statement,
            'author' => $book->main_author,
            'format' => $book->content_type,
            'edition' => $book->edition,
            'published' => $published !== '' ? $published : null,
            'isbn' => $book->isbn,
            'call_number' => $book->call_number,
            'cover_url' => $this->bookCoverUrl($book->cover_image),
            'general_note' => $book->general_note,
            'physical_description' => $physical !== '' ? $physical : null,
            'bibliography' => $book->bibliography_note,
            'subject_topic' => $book->subject_topic,
            'subject_form' => $book->subject_form,
            'genre' => $book->genre,
            'series' => $book->series_title,
        ];
    }

    private function formatBookCopy(Book $copy): array
    {
        return [
            'id' => $copy->id,
            'accession_no' => $copy->accession_no,
            'call_number' => $copy->call_number,
            'volume' => $copy->volume,
            'collection' => $copy->course,
            'shelving_location' => trim(implode(' - ', array_filter([$copy->library_name, $copy->section]))),
            'circulation_type' => 'Regular circulation',
            'circulation_status' => match ($copy->availability) {
                Book::AVAILABILITY_AVAILABLE => 'On-Shelf',
                Book::AVAILABILITY_ON_HOLD => 'On hold',
                default => 'Checked out',
            },
            'availability' => $copy->availability,
            'is_held' => $copy->availability === Book::AVAILABILITY_ON_HOLD,
            'can_add_to_cart' => $copy->availability === Book::AVAILABILITY_AVAILABLE,
            'barcode' => $copy->barcode,
            'rfid' => $copy->rfid,
        ];
    }

    private function formatEbook(Ebook $ebook): array
    {
        return [
            'id' => $ebook->id,
            'type' => 'ebook',
            'title' => $ebook->title,
            'author' => $ebook->author,
            'publication_year' => $ebook->publication_year,
            'publisher' => $ebook->publisher,
            'source' => $ebook->source,
            'link' => $ebook->link,
            'program' => $ebook->program?->program_name,
            'course' => $ebook->course?->course_name,
        ];
    }

    private function distinctBookValues(string $column): array
    {
        return Book::query()
            ->whereNull('archived_at')
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->values()
            ->all();
    }

    private function bookCoverUrl(?string $coverImage): string
    {
        if (filled($coverImage)) {
            return asset('storage/'.$coverImage);
        }

        return asset('images/defaultBook.png');
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
}
