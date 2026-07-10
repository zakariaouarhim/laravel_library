<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\ReaderStagingBook;
use App\Services\BookImportService;
use App\Services\DescriptionRewriteService;
use App\Services\EnrichPreviewService;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function enrichPreview(Request $request, ReaderStagingBook $staged, EnrichPreviewService $enricher): JsonResponse
    {
        // Use the admin's current (possibly unsaved) modal values for the search.
        $name     = (string) $request->input('name', $staged->name);
        $author   = (string) $request->input('author', $staged->author ?? '');
        $isbnIn   = $request->input('isbn', $staged->isbn);
        $language = $request->input('language', $staged->language) ?: 'arabic';

        $result = $enricher->preview($name, $author, $isbnIn, $language, [
            'description' => $staged->description,
            'page_num'    => $staged->page_num,
            'publisher'   => $staged->publisher,
            'language'    => $staged->language,
            'isbn'        => $isbnIn,
        ]);

        return response()->json($result['body'], $result['status']);
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
    public function approve(Request $request, ReaderStagingBook $staged, BookImportService $importer): JsonResponse
    {
        if ($staged->status === 'imported') {
            return response()->json(['success' => false, 'message' => 'سبق استيراد هذا الكتاب.'], 409);
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

        // Cover: an admin-replaced webp is already in public/; otherwise process
        // the original reader_DB file.
        $prefix = 'reader_' . substr($staged->external_id, 0, 8);
        $cover  = ['prefix' => $prefix];
        if ($staged->custom_image) {
            $cover['webp'] = $staged->custom_image;
        } elseif ($staged->local_image) {
            $cover['file'] = base_path('reader_DB' . DIRECTORY_SEPARATOR . $staged->local_image);
        }

        try {
            $book = $importer->create(
                $data,
                $cover,
                ['rewritten' => (bool) $staged->description_rewritten, 'original_description' => $staged->original_description],
                fn(Book $book) => $staged->update([
                    'status'      => 'imported',
                    'book_id'     => $book->id,
                    'reviewed_at' => now(),
                ])
            );

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

    /**
     * Undo an import: send an imported row back to "pending" and soft-delete the
     * Book that was created from it (recoverable, and Scout drops it from search),
     * so the admin can review/re-import without leaving a duplicate behind.
     */
    public function restore(ReaderStagingBook $staged): JsonResponse
    {
        if ($staged->status !== 'imported') {
            return response()->json(['success' => false, 'message' => 'هذا الكتاب ليس في قائمة المستوردة.'], 422);
        }

        if ($staged->book_id) {
            $book = Book::find($staged->book_id);
            if ($book) {
                // Soft-delete without depending on Meilisearch being up (mirrors
                // approve), then best-effort drop it from the search index.
                Book::withoutSyncingToSearch(fn() => $book->delete());
                try {
                    $book->unsearchable();
                } catch (\Throwable $e) {
                    \Log::warning('reader-import restore: unsearchable failed: ' . $e->getMessage());
                }
            }
        }

        $staged->update(['status' => 'pending', 'book_id' => null, 'reviewed_at' => null]);

        return response()->json(['success' => true]);
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
