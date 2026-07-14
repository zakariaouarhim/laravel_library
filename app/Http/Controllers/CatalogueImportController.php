<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\CatalogueReference;
use App\Models\CatalogueReview;
use App\Services\BookImportService;
use App\Services\CategoryKeywordSuggester;
use App\Services\DescriptionRewriteService;
use App\Services\EnrichPreviewService;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only browser over the ~81k-row reference catalogue: filter, review and
 * approve items into real Books. Reuses the reader-import review UX. Per-row
 * state lives in catalogue_reviews (the catalogue table itself is read-only).
 */
class CatalogueImportController extends Controller
{
    private const PER_PAGE = 24;

    /** langue (source) -> our language code. */
    private const LANG_MAP = [
        'arabe' => 'arabic', 'français' => 'french', 'francais' => 'french',
        'anglais' => 'english', 'espagnol' => 'spanish', 'allemand' => 'german',
    ];

    /** our language code -> a langue LIKE token for filtering. */
    private const LANG_FILTER = [
        'arabic' => 'Arabe', 'french' => 'Français', 'english' => 'Anglais',
        'spanish' => 'Espagnol', 'german' => 'Allemand',
    ];

    public function index()
    {
        return view('admin.catalogue-import.index', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    /** Paginated catalogue rows + filters + status counts. */
    public function list(Request $request, CategoryKeywordSuggester $suggester): JsonResponse
    {
        $data = $request->validate([
            'status'           => 'nullable|in:pending,imported,skipped,all',
            'page'             => 'nullable|integer|min:1',
            'q'                => 'nullable|string|max:191',
            'language'         => 'nullable|in:arabic,french,english,spanish,german',
            'min_completeness' => 'nullable|integer|min:0|max:8',
            'has_description'  => 'nullable|boolean',
            'hide_in_store'    => 'nullable|boolean',
        ]);

        $status = $data['status'] ?? 'pending';
        $minC   = $data['min_completeness'] ?? 7;

        $query = CatalogueReference::query()
            ->leftJoin('catalogue_reviews', 'catalogue_reviews.catalogue_reference_id', '=', 'catalogue_reference.id')
            ->select('catalogue_reference.*', 'catalogue_reviews.status as review_status', 'catalogue_reviews.book_id as review_book_id')
            ->where('catalogue_reference.completeness', '>=', $minC)
            ->when($status === 'pending',  fn($q) => $q->whereNull('catalogue_reviews.id'))
            ->when($status === 'imported', fn($q) => $q->where('catalogue_reviews.status', 'imported'))
            ->when($status === 'skipped',  fn($q) => $q->where('catalogue_reviews.status', 'skipped'))
            ->when(!empty($data['language']), fn($q) => $q->where('catalogue_reference.langue', 'like', '%' . self::LANG_FILTER[$data['language']] . '%'))
            ->when(!empty($data['has_description']), fn($q) => $q->whereNotNull('catalogue_reference.description')->where('catalogue_reference.description', '<>', ''))
            ->when(!empty($data['q']), function ($q) use ($data) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $data['q']) . '%';
                $q->where(fn($w) => $w->where('catalogue_reference.title', 'like', $like)
                    ->orWhere('catalogue_reference.author', 'like', $like)
                    ->orWhere('catalogue_reference.isbn', 'like', $like));
            })
            ->when(!empty($data['hide_in_store']), fn($q) => $q->whereNotExists(fn($sub) => $sub
                ->from('books')
                ->whereColumn('books.isbn', 'catalogue_reference.isbn')
                ->whereNotNull('catalogue_reference.isbn')))
            ->orderByDesc('catalogue_reference.completeness')
            ->orderBy('catalogue_reference.id');

        $page = $query->paginate(self::PER_PAGE);

        // Which of this page's ISBNs already exist as real books (dedup hint).
        $inStore = Book::whereIn('isbn', collect($page->items())->pluck('isbn')->filter()->all())
            ->pluck('isbn')->all();
        $inStore = array_flip($inStore);

        return response()->json([
            'books'     => collect($page->items())->map(fn($r) => $this->present($r, $inStore, $suggester))->values(),
            'has_more'  => $page->hasMorePages(),
            'next_page' => $page->currentPage() + 1,
            'total'     => $page->total(),
            'counts'    => $this->counts(),
        ]);
    }

    /** Global status counts for the header. */
    private function counts(): array
    {
        $total    = CatalogueReference::count();
        $imported = CatalogueReview::where('status', 'imported')->count();
        $skipped  = CatalogueReview::where('status', 'skipped')->count();

        return [
            'total'    => $total,
            'imported' => $imported,
            'skipped'  => $skipped,
            'pending'  => $total - $imported - $skipped,
        ];
    }

    /** Serialize a catalogue row for the grid + detail modal. */
    private function present($r, array $inStore, CategoryKeywordSuggester $suggester): array
    {
        $language  = $this->mapLangue($r->langue);
        $suggested = $suggester->suggest($r->title, $r->edition, $language);

        return [
            'id'          => $r->id,
            'name'        => $r->title,
            'author'      => $r->author,
            'isbn'        => $r->isbn,
            'page_num'    => (int) preg_replace('/\D/', '', (string) $r->pages) ?: null,
            'publisher'   => $r->edition, // `edition` holds the publisher
            'language'    => $language,
            'price'       => $this->parsePrice($r->price),
            'description' => $r->description,
            'cover_url'   => CatalogueReference::normalizeCoverUrl($r->cover_url),
            'completeness' => (int) $r->completeness,
            'category_ids'         => $suggested['category_ids'],
            'primary_category_id'  => $suggested['primary_category_id'],
            'in_store'    => isset($r->isbn) && isset($inStore[$r->isbn]),
            'status'      => $r->review_status ?: 'pending',
            'book_id'     => $r->review_book_id,
        ];
    }

    /** Enrich preview from all sources (shared with reader-import). */
    public function enrichPreview(Request $request, CatalogueReference $catalogue, EnrichPreviewService $enricher): JsonResponse
    {
        $name     = (string) $request->input('name', $catalogue->title);
        $author   = (string) $request->input('author', $catalogue->author ?? '');
        $isbnIn   = $request->input('isbn', $catalogue->isbn);
        $language = $request->input('language') ?: $this->mapLangue($catalogue->langue) ?: 'arabic';

        $result = $enricher->preview($name, $author, $isbnIn, $language, [
            'description' => $catalogue->description,
            'page_num'    => (int) preg_replace('/\D/', '', (string) $catalogue->pages) ?: null,
            'publisher'   => $catalogue->edition,
            'language'    => $this->mapLangue($catalogue->langue),
            'isbn'        => $isbnIn,
        ]);

        return response()->json($result['body'], $result['status']);
    }

    /** AI-rewrite the description for SEO. No persistence — returns the text. */
    public function rewriteDescription(Request $request, CatalogueReference $catalogue, DescriptionRewriteService $rewriter): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'nullable|string|max:500',
            'author'      => 'nullable|string|max:300',
            'description' => 'nullable|string',
            'language'    => 'nullable|in:arabic,english,french',
        ]);

        $name        = trim($data['name'] ?? '') ?: $catalogue->title;
        $author      = $data['author'] ?? $catalogue->author;
        $language    = $data['language'] ?? $this->mapLangue($catalogue->langue) ?: 'arabic';
        $description = trim($data['description'] ?? '') ?: (string) $catalogue->description;

        if ($description === '') {
            return response()->json(['success' => false, 'message' => 'لا يوجد وصف لإعادة صياغته.'], 422);
        }

        $result = $rewriter->rewrite($name, $author, $description, $language);

        if (!$result['ok']) {
            return response()->json(['success' => false, 'message' => 'فشلت إعادة الصياغة: ' . $result['error']], 502);
        }

        return response()->json(['success' => true, 'description' => $result['text']]);
    }

    /** Process an uploaded cover to webp. No persistence — returns the path. */
    public function uploadImage(Request $request, CatalogueReference $catalogue, ImageService $imageService): JsonResponse
    {
        $request->validate(['image' => 'required|image|max:5120']);

        $path = $imageService->processLocalFile($request->file('image')->getRealPath(), 'images/books', 'cat_' . $catalogue->id);

        if (!$path) {
            return response()->json(['success' => false, 'message' => 'تعذّر معالجة الصورة.'], 500);
        }

        return response()->json(['success' => true, 'path' => $path, 'image_version' => time()]);
    }

    /** Download a cover from a URL to webp. No persistence — returns the path. */
    public function imageFromUrl(Request $request, CatalogueReference $catalogue, ImageService $imageService): JsonResponse
    {
        $data = $request->validate(['url' => 'required|url']);

        $path = $imageService->downloadFromUrl($data['url'], 'images/books', 'cat_' . $catalogue->id);

        if (!$path) {
            return response()->json(['success' => false, 'message' => 'تعذّر تنزيل الصورة.'], 500);
        }

        return response()->json(['success' => true, 'path' => $path, 'image_version' => time()]);
    }

    /** Approve a catalogue item: create the real Book, then record the review. */
    public function approve(Request $request, CatalogueReference $catalogue, BookImportService $importer): JsonResponse
    {
        $existing = CatalogueReview::where('catalogue_reference_id', $catalogue->id)->first();
        if ($existing && $existing->status === 'imported') {
            return response()->json(['success' => false, 'message' => 'سبق استيراد هذا العنصر.'], 409);
        }

        // Clean enrich-sourced values so they can't hard-fail validation or
        // overflow the books columns (isbn varchar 17, title varchar 191).
        $request->merge([
            'isbn' => BookImportService::normalizeIsbn($request->input('isbn')),
            'name' => mb_substr(trim((string) $request->input('name')), 0, 191),
        ]);

        $data = $request->validate([
            'name'                => 'required|string|max:500',
            'author'              => 'nullable|string|max:300',
            'isbn'                => 'nullable|string|max:20',
            'page_num'            => 'nullable|integer|min:0',
            'publisher'           => 'nullable|string|max:300',
            'language'            => 'required|in:arabic,english,french',
            'price'               => 'required|numeric|min:0',
            'description'         => 'nullable|string',
            'quantity'            => 'required|integer|min:0',
            'category_ids'        => 'required|array|min:1',
            'category_ids.*'      => 'integer|exists:categories,id',
            'primary_category_id' => 'required|integer|in_array:category_ids.*',
            'custom_image'        => 'nullable|string|max:255',
            'rewritten'           => 'nullable|boolean',
            'original_description' => 'nullable|string',
            'force'               => 'nullable|boolean',
        ]);

        if (empty($data['force'])) {
            $dupe = Book::where('title', $data['name'])
                ->orWhere(fn($q) => $q->whereNotNull('isbn')->where('isbn', $data['isbn'] ?: '__none__'))
                ->first();
            if ($dupe) {
                return response()->json([
                    'success'   => false,
                    'duplicate' => true,
                    'message'   => 'يوجد كتاب بنفس العنوان أو ISBN. أعد الإرسال للتأكيد أو تخطَّ.',
                ], 409);
            }
        }

        // Cover: an admin-replaced webp is already in public/; else download the
        // catalogue's remote cover.
        $cover = ['prefix' => 'cat_' . $catalogue->id];
        if (!empty($data['custom_image'])) {
            $cover['webp'] = $data['custom_image'];
        } elseif ($catalogue->coverUrl()) {
            $cover['url'] = $catalogue->coverUrl();
        }

        try {
            $book = $importer->create(
                $data,
                $cover,
                ['rewritten' => !empty($data['rewritten']), 'original_description' => $data['original_description'] ?? null],
                fn(Book $book) => CatalogueReview::updateOrCreate(
                    ['catalogue_reference_id' => $catalogue->id],
                    ['status' => 'imported', 'book_id' => $book->id, 'reviewed_at' => now()]
                )
            );

            return response()->json(['success' => true, 'book_id' => $book->id, 'message' => 'تم إنشاء الكتاب.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Mark a catalogue item as skipped. */
    public function skip(CatalogueReference $catalogue): JsonResponse
    {
        $review = CatalogueReview::firstOrNew(['catalogue_reference_id' => $catalogue->id]);
        if ($review->status === 'imported') {
            return response()->json(['success' => false, 'message' => 'العنصر مستورد بالفعل.'], 409);
        }
        $review->fill(['status' => 'skipped', 'reviewed_at' => now()])->save();

        return response()->json(['success' => true]);
    }

    /** Send a skipped item back to pending (delete its review row). */
    public function unskip(CatalogueReference $catalogue): JsonResponse
    {
        CatalogueReview::where('catalogue_reference_id', $catalogue->id)
            ->where('status', 'skipped')
            ->delete();

        return response()->json(['success' => true]);
    }

    /** 'Arabe / Français' -> first recognized code; null if none. */
    private function mapLangue(?string $langue): ?string
    {
        if (!$langue) {
            return null;
        }
        foreach (preg_split('/[\/,]/', $langue) as $part) {
            $key = mb_strtolower(trim($part));
            if (isset(self::LANG_MAP[$key])) {
                return self::LANG_MAP[$key];
            }
        }
        return null;
    }

    /** "50,00 DH" -> 50.00 */
    private function parsePrice(?string $price): float
    {
        if (!$price) {
            return 0.0;
        }
        $clean = preg_replace('/[^\d,\.]/', '', $price);
        // Moroccan format uses comma as the decimal separator.
        $clean = str_replace(',', '.', str_replace('.', '', $clean));

        return round((float) $clean, 2);
    }

    /** Category tree for the review screen (bold parents + ── children). */
    private function categoryOptions(): array
    {
        $cats     = Category::orderBy('name')->get(['id', 'name', 'parent_id']);
        $byParent = $cats->groupBy('parent_id');

        $result  = [];
        $emitted = [];

        foreach ($cats->whereNull('parent_id')->sortBy('name') as $parent) {
            $result[]             = ['id' => $parent->id, 'name' => $parent->name, 'parent' => true];
            $emitted[$parent->id] = true;

            foreach (($byParent[$parent->id] ?? collect())->sortBy('name') as $child) {
                $result[]            = ['id' => $child->id, 'name' => $child->name, 'parent' => false];
                $emitted[$child->id] = true;
            }
        }

        foreach ($cats as $c) {
            if (!isset($emitted[$c->id])) {
                $result[] = ['id' => $c->id, 'name' => $c->name, 'parent' => false];
            }
        }

        return $result;
    }
}
