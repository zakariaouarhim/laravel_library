<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\PublishingHouse;
use App\Models\ReaderStagingBook;
use App\Services\DescriptionRewriteService;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only tool to review scraped "reader" books one by one and approve them
 * into the real catalogue. Backed by the reader_staging_books table.
 */
class ReaderImportController extends Controller
{
    private const PER_PAGE = 24;

    /** The review screen. */
    public function index()
    {
        return view('admin.reader-import.index', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    /** Paginated staging rows for the grid + status counts. */
    public function list(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => 'nullable|in:pending,imported,skipped,all',
            'page'   => 'nullable|integer|min:1',
            'q'      => 'nullable|string|max:191',
        ]);

        $status = $data['status'] ?? 'pending';

        $query = ReaderStagingBook::query()
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when(!empty($data['q']), function ($q) use ($data) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $data['q']) . '%';
                $q->where('name', 'like', $like)->orWhere('author', 'like', $like);
            })
            ->orderBy('id');

        $page = $query->paginate(self::PER_PAGE);

        // Titles that already exist in the catalogue (dedup hint for this page).
        $existingTitles = Book::whereIn('title', collect($page->items())->pluck('name'))
            ->pluck('title')->map(fn($t) => mb_strtolower(trim($t)))->all();

        return response()->json([
            'books'     => collect($page->items())->map(fn(ReaderStagingBook $b) => $this->present($b, $existingTitles))->values(),
            'has_more'  => $page->hasMorePages(),
            'next_page' => $page->currentPage() + 1,
            'counts'    => [
                'pending'  => ReaderStagingBook::where('status', 'pending')->count(),
                'imported' => ReaderStagingBook::where('status', 'imported')->count(),
                'skipped'  => ReaderStagingBook::where('status', 'skipped')->count(),
                'total'    => ReaderStagingBook::count(),
            ],
        ]);
    }

    /** Serialize a staging row for the grid + detail modal. */
    private function present(ReaderStagingBook $b, array $existingTitles = []): array
    {
        return [
            'id'           => $b->id,
            'name'         => $b->name,
            'author'       => $b->author,
            'isbn'         => $b->isbn,
            'page_num'     => $b->page_num,
            'publisher'    => $b->publisher,
            'language'     => $b->language,
            'price'        => (float) $b->price,
            'description'  => $b->description,
            'stock'        => $b->stock,
            'category_ids' => array_map('intval', $b->category_ids ?? []),
            'primary_category_id' => $b->primary_category_id ? (int) $b->primary_category_id : null,
            'source_cats'  => $b->source_categories,
            'image_exists' => $b->image_exists || (bool) $b->custom_image,
            'description_rewritten' => (bool) $b->description_rewritten,
            'status'       => $b->status,
            'book_id'      => $b->book_id,
            'duplicate'    => in_array(mb_strtolower(trim($b->name)), $existingTitles, true),
        ];
    }

    /** Stream a staged book's cover: admin-replaced one if set, else the reader_DB file. */
    public function image(ReaderStagingBook $staged)
    {
        if ($staged->custom_image && is_file(public_path($staged->custom_image))) {
            return response()->file(public_path($staged->custom_image));
        }

        $path = base_path('reader_DB' . DIRECTORY_SEPARATOR . $staged->local_image);
        if (!$staged->local_image || !is_file($path)) {
            abort(404);
        }

        return response()->file($path);
    }

    /**
     * Preview book data WITHOUT writing anything. Queries our local reference
     * catalogue (~81k almouggar.com rows — instant, free, strong on Arabic/French)
     * AND the language-aware web pipeline (BNF/Google Books/Open Library/Wikipedia
     * — BNF only for French, Wikipedia in the book's language). Rather than merging,
     * it returns EVERY source's value per field so the admin picks which one to
     * apply in the modal. Sources are ordered catalogue > BNF > Google > Open
     * Library > Wikipedia (only affects the default selection).
     */
    public function enrichPreview(Request $request, ReaderStagingBook $staged): JsonResponse
    {
        // Use the admin's current (possibly unsaved) modal values for the search.
        $name     = trim((string) $request->input('name', $staged->name));
        $author   = trim((string) $request->input('author', $staged->author ?? ''));
        $isbnIn   = $request->input('isbn', $staged->isbn);
        $language = $request->input('language', $staged->language) ?: 'arabic';

        try {
            $results = [];
            $method  = null;

            // 1) Local reference catalogue — always, does its own isbn/title match.
            if ($name !== '' || $this->cleanIsbn($isbnIn)) {
                $cat = app(\App\Services\CatalogueLookupService::class)->lookup($isbnIn, $name, $author);
                if ($cat) {
                    $results['catalogue'] = $cat;
                    $method = 'catalogue';
                }
            }

            // 2) Web pipeline — prefer ISBN lookup, fall back to title+author.
            $svc = app(\App\Services\BookIngestionService::class);
            $web = [];
            $isbn = $this->cleanIsbn($isbnIn);
            if ($isbn) {
                $web = $svc->resolveSourcesByIsbn($isbn, $language);
                if ($web) $method = $method ?: 'ISBN';
            }
            if (empty($web) && $name !== '') {
                $web = $svc->resolveSources($name, $author, $language);
                if ($web) $method = $method ?: 'title+author';
            }
            $results += $web; // keep catalogue key first

            if ($name === '' && !$isbn) {
                return response()->json(['success' => false, 'message' => 'لا يوجد عنوان أو ISBN للبحث.'], 422);
            }
            if (empty($results)) {
                return response()->json(['success' => true, 'found' => false, 'message' => 'لم يتم العثور على بيانات في أي مصدر.']);
            }

            // Ordered list of every source's value for a field (default = first).
            $priority = ['catalogue', 'bnf', 'google_books', 'open_library', 'wikipedia'];
            $labels   = ['catalogue' => 'الكتالوج', 'bnf' => 'BNF', 'google_books' => 'Google Books', 'open_library' => 'Open Library', 'wikipedia' => 'Wikipedia'];
            $options = function (string $field, ?callable $transform = null) use ($results, $priority, $labels) {
                $out = [];
                foreach ($priority as $src) {
                    if (empty($results[$src]) || empty($results[$src][$field])) continue;
                    $value = $transform ? $transform($results[$src][$field]) : $results[$src][$field];
                    if ($value === null || $value === '' || $value === 0) continue;
                    $out[] = ['source' => $src, 'label' => $labels[$src], 'value' => $value];
                }
                return $out;
            };

            $fields = array_filter([
                'description' => $options('description', fn($v) => trim(strip_tags($v))),
                'image'       => $options('image_url'),
                'page_num'    => $options('page_num', fn($v) => (int) $v > 0 ? (int) $v : null),
                'publisher'   => $options('publisher', fn($v) => trim($v)),
                'language'    => $options('language'),
                'isbn'        => $options('isbn'),
            ], fn($o) => !empty($o));

            return response()->json([
                'success'       => true,
                'found'         => !empty($fields),
                'search_method' => $method,
                'sources'       => array_values(array_map(fn($s) => $labels[$s] ?? $s, array_keys($results))),
                'current'       => [
                    'description' => $staged->description,
                    'page_num'    => $staged->page_num,
                    'publisher'   => $staged->publisher,
                    'language'    => $staged->language,
                    'isbn'        => $isbnIn,
                ],
                'fields'        => $fields,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'تعذّر الاتصال بالمصادر: ' . $e->getMessage()], 500);
        }
    }

    /** AI-rewrite the description for SEO (keeps the original); persists to staging. */
    public function rewriteDescription(Request $request, ReaderStagingBook $staged, DescriptionRewriteService $rewriter): JsonResponse
    {
        // Rewrite the admin's current (possibly unsaved) text, not just the stored one.
        $data = $request->validate([
            'name'        => 'nullable|string|max:500',
            'author'      => 'nullable|string|max:300',
            'description' => 'nullable|string',
            'language'    => 'nullable|in:arabic,english,french',
        ]);

        $name        = trim($data['name'] ?? '') ?: $staged->name;
        $author      = $data['author'] ?? $staged->author;
        $language    = $data['language'] ?? $staged->language;
        $description = trim($data['description'] ?? '') ?: (string) $staged->description;

        if ($description === '') {
            return response()->json(['success' => false, 'message' => 'لا يوجد وصف لإعادة صياغته.'], 422);
        }

        $result = $rewriter->rewrite($name, $author, $description, $language);

        if (!$result['ok']) {
            return response()->json(['success' => false, 'message' => 'فشلت إعادة الصياغة: ' . $result['error']], 502);
        }

        $staged->update([
            'original_description'  => $staged->original_description ?: $description,
            'description'           => $result['text'],
            'description_rewritten' => true,
        ]);

        return response()->json(['success' => true, 'description' => $result['text']]);
    }

    /** Replace the cover from an uploaded file (converted to webp). */
    public function uploadImage(Request $request, ReaderStagingBook $staged, ImageService $imageService): JsonResponse
    {
        $request->validate(['image' => 'required|image|max:5120']);

        $path = $imageService->processLocalFile(
            $request->file('image')->getRealPath(),
            'images/books',
            'reader_' . substr($staged->external_id, 0, 8)
        );

        if (!$path) {
            return response()->json(['success' => false, 'message' => 'تعذّر معالجة الصورة.'], 500);
        }

        $staged->update(['custom_image' => $path]);

        return response()->json(['success' => true, 'image_version' => time()]);
    }

    /** Replace the cover from a URL (e.g. the Google Books cover). */
    public function imageFromUrl(Request $request, ReaderStagingBook $staged, ImageService $imageService): JsonResponse
    {
        $data = $request->validate(['url' => 'required|url']);

        $path = $imageService->downloadFromUrl($data['url'], 'images/books', 'reader_' . substr($staged->external_id, 0, 8));

        if (!$path) {
            return response()->json(['success' => false, 'message' => 'تعذّر تنزيل الصورة.'], 500);
        }

        $staged->update(['custom_image' => $path]);

        return response()->json(['success' => true, 'image_version' => time()]);
    }

    /** Approve a staged book: create the real Book, then mark the row imported. */
    public function approve(Request $request, ReaderStagingBook $staged, ImageService $imageService): JsonResponse
    {
        if ($staged->status === 'imported') {
            return response()->json(['success' => false, 'message' => 'سبق استيراد هذا الكتاب.'], 409);
        }

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
            'force'               => 'nullable|boolean',
        ]);

        if (empty($data['force'])) {
            $dupe = Book::where('title', $data['name'])->first();
            if ($dupe) {
                return response()->json([
                    'success'   => false,
                    'duplicate' => true,
                    'message'   => 'يوجد كتاب بنفس العنوان. أعد الإرسال للتأكيد أو تخطَّ.',
                ], 409);
            }
        }

        try {
            // Keep model events (slug, observers) but don't hard-depend on Meilisearch
            // being up during import; books are indexed later via `scout:import`.
            $book = Book::withoutSyncingToSearch(fn() => DB::transaction(function () use ($data, $staged, $imageService) {
                $authorId = null;
                if (!empty($data['author'])) {
                    $authorId = Author::firstOrCreate(
                        ['name' => trim($data['author'])],
                        ['status' => 'active']
                    )->id;
                }

                $publisherId = null;
                if (!empty($data['publisher'])) {
                    $publisherId = PublishingHouse::firstOrCreate(
                        ['name' => trim($data['publisher'])],
                        ['status' => 'active']
                    )->id;
                }

                // Cover: an admin-replaced webp is already in public/; otherwise
                // process the original reader_DB file.
                $imagePath = null;
                if ($staged->custom_image && is_file(public_path($staged->custom_image))) {
                    $imagePath = $staged->custom_image;
                } else {
                    $fullImage = base_path('reader_DB' . DIRECTORY_SEPARATOR . $staged->local_image);
                    if ($staged->local_image && is_file($fullImage)) {
                        $imagePath = $imageService->processLocalFile(
                            $fullImage,
                            'images/books',
                            'reader_' . substr($staged->external_id, 0, 8)
                        );
                    }
                }

                $attrs = [
                    'title'           => $data['name'],
                    'type'            => 'book',
                    'product_type'    => 'standard',
                    'author_id'       => $authorId,
                    'description'     => $data['description'] ?: null,
                    'price'           => $data['price'],
                    'discount'        => 0,
                    'category_id'     => $data['primary_category_id'],
                    'image'           => $imagePath,
                    'page_num'        => $data['page_num'] ?? 0,
                    'language'        => $data['language'],
                    'publishing_house_id' => $publisherId,
                    'isbn'            => $data['isbn'] ?: null,
                    'quantity'        => (int) $data['quantity'],
                    'api_data_status' => 'pending',
                    'status'          => 'active',
                ];

                $book = Book::create($attrs);

                // If the description was AI-rewritten, preserve the original and mark
                // it so the nightly rewrite cron skips it. These columns aren't in
                // Book::$fillable, so set them explicitly via forceFill.
                if ($staged->description_rewritten) {
                    $book->forceFill([
                        'original_description' => $staged->original_description,
                        'rewrite_status'       => 'rewritten',
                        'rewritten_at'         => now(),
                    ])->save();
                }

                if ($authorId) {
                    $book->authors()->syncWithoutDetaching([$authorId => ['author_type' => 'primary']]);
                }
                $book->syncCategories($data['category_ids'], $data['primary_category_id']);

                $staged->update([
                    'status'      => 'imported',
                    'book_id'     => $book->id,
                    'reviewed_at' => now(),
                ]);

                return $book;
            }));

            return response()->json([
                'success' => true,
                'book_id' => $book->id,
                'message' => 'تم إنشاء الكتاب.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Mark a staged book as skipped (won't be imported). */
    public function skip(ReaderStagingBook $staged): JsonResponse
    {
        if ($staged->status === 'imported') {
            return response()->json(['success' => false, 'message' => 'الكتاب مستورد بالفعل.'], 409);
        }
        $staged->update(['status' => 'skipped', 'reviewed_at' => now()]);

        return response()->json(['success' => true]);
    }

    /** Send a skipped book back to pending. */
    public function unskip(ReaderStagingBook $staged): JsonResponse
    {
        if ($staged->status !== 'skipped') {
            return response()->json(['success' => false], 422);
        }
        $staged->update(['status' => 'pending', 'reviewed_at' => null]);

        return response()->json(['success' => true]);
    }

    /** Normalize an ISBN to 10/13 digits, or null if invalid. */
    private function cleanIsbn(?string $isbn): ?string
    {
        if (empty($isbn)) {
            return null;
        }
        $clean = preg_replace('/[^0-9X]/', '', strtoupper($isbn));

        return in_array(strlen($clean), [10, 13], true) ? $clean : null;
    }

    /**
     * Category list for the review screen, ordered as a tree: each top-level
     * parent (bold) immediately followed by its children (indented with ──) —
     * matching the dashboard's product filter select.
     */
    private function categoryOptions(): array
    {
        $cats     = Category::orderBy('name')->get(['id', 'name', 'parent_id']);
        $byParent = $cats->groupBy('parent_id');

        $result  = [];
        $emitted = [];

        foreach ($cats->whereNull('parent_id')->sortBy('name') as $parent) {
            $result[]            = ['id' => $parent->id, 'name' => $parent->name, 'parent' => true];
            $emitted[$parent->id] = true;

            foreach (($byParent[$parent->id] ?? collect())->sortBy('name') as $child) {
                $result[]           = ['id' => $child->id, 'name' => $child->name, 'parent' => false];
                $emitted[$child->id] = true;
            }
        }

        // Safety net: any category whose parent wasn't a top-level node.
        foreach ($cats as $c) {
            if (!isset($emitted[$c->id])) {
                $result[] = ['id' => $c->id, 'name' => $c->name, 'parent' => false];
            }
        }

        return $result;
    }
}
