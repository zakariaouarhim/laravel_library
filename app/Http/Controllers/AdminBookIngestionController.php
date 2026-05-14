<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PendingBook;
use App\Services\BookIngestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminBookIngestionController extends Controller
{
    public function __construct(
        private BookIngestionService $ingestion,
    ) {}

    /**
     * GET /admin/books/ingest — form to stage a single (title, author) row.
     */
    public function create()
    {
        return view('Dashbord_Admin.books-ingest');
    }

    /**
     * POST /admin/books/ingest — runs lookup, redirects to review page.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'     => 'required|string|max:191',
            'author'    => 'required|string|max:191',
            'author_id' => 'nullable|integer|exists:authors,id',
            'language'  => 'required|string|in:arabic,english,french,spanish,german',
            'force'     => 'nullable',
        ]);

        $pending = $this->ingestion->stageFromTitleAuthor(
            $validated['title'],
            $validated['author'],
            $validated['language'],
            (bool) ($validated['force'] ?? false),
            $validated['author_id'] ?? null
        );

        return redirect()
            ->route('admin.books.pending.show', $pending->id)
            ->with('success', 'تم جلب البيانات. راجعها قبل الاعتماد.');
    }

    /**
     * POST /admin/books/ingest-isbn — ISBN-only staging. More accurate when admin
     * has the physical book on the desk.
     */
    public function storeFromIsbn(Request $request)
    {
        $validated = $request->validate([
            'isbn'     => 'required|string|regex:/^[\d\-Xx ]+$/',
            'language' => 'required|string|in:arabic,english,french,spanish,german',
            'force'    => 'nullable',
        ]);

        try {
            $pending = $this->ingestion->stageFromIsbn(
                $validated['isbn'],
                $validated['language'],
                (bool) ($validated['force'] ?? false)
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['isbn' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.books.pending.show', $pending->id)
            ->with('success', 'تم جلب البيانات من الـISBN. راجعها قبل الاعتماد.');
    }

    /**
     * GET /admin/books/pending — list of staged rows with optional status filter.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status');

        $query = PendingBook::query()
            ->with(['existingBook:id,title', 'approvedBook:id,title'])
            ->latest();

        if ($statusFilter && in_array($statusFilter, [
            PendingBook::STATUS_ENRICHED,
            PendingBook::STATUS_FAILED,
            PendingBook::STATUS_DUPLICATE,
            PendingBook::STATUS_APPROVED,
            PendingBook::STATUS_DISCARDED,
        ], true)) {
            $query->where('status', $statusFilter);
        }

        $pending = $query->paginate(25)->appends($request->query());

        $counts = PendingBook::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('Dashbord_Admin.books-pending-index', [
            'pendingBooks' => $pending,
            'counts'       => $counts,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * GET /admin/books/pending/{id} — review page.
     */
    public function show(PendingBook $pendingBook)
    {
        // Top-level categories filtered by language (NULL = universal). Children
        // are eager-loaded with the same filter so the picker can render a
        // parent → children accordion.
        $languageFilter = function ($q) use ($pendingBook) {
            $q->whereNull('language')->orWhere('language', $pendingBook->language);
        };
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where($languageFilter)
            ->with(['children' => function ($q) use ($languageFilter) {
                $q->where($languageFilter)->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return view('Dashbord_Admin.books-pending-review', [
            'pendingBook' => $pendingBook,
            'categories'  => $categories,
        ]);
    }

    /**
     * POST /admin/books/pending/{id}/approve — convert to a live Book.
     */
    public function approve(Request $request, PendingBook $pendingBook)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:191',
            'author_name'    => 'required|string|max:191',
            'isbn'           => 'nullable|string|max:32',
            'description'    => 'nullable|string',
            'page_num'       => 'nullable|integer|min:0',
            'publisher_name' => 'nullable|string|max:191',
            'language'       => 'required|string|in:arabic,english,french,spanish,german',
            'price'          => 'required|numeric|min:0',
            'quantity'       => 'required|integer|min:0',
            'category_ids'   => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'image_source'   => 'nullable|in:bnf,google_books,open_library,wikipedia,custom',
            'uploaded_cover' => 'required_if:image_source,custom|nullable|image|max:5120',
        ]);

        // Pass the uploaded file (if any) through to the service via overrides.
        $validated['uploaded_cover'] = $request->file('uploaded_cover');

        try {
            $book = $this->ingestion->approve(
                $pendingBook,
                $validated,
                Auth::id()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.books.pending.index')
            ->with('success', "تم إنشاء الكتاب: {$book->title} (#{$book->id})");
    }

    /**
     * DELETE /admin/books/pending/{id} — discard a pending row.
     */
    public function discard(PendingBook $pendingBook)
    {
        $this->ingestion->discard($pendingBook, Auth::id());

        return redirect()
            ->route('admin.books.pending.index')
            ->with('success', 'تم تجاهل الإدخال.');
    }
}
