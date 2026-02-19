<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\BookAuthor;
use App\Models\PublishingHouse;
use App\Models\Category;
use App\Services\AuthorEnrichmentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    /**
     * Public: Browse all authors
     */
    public function publicIndex(Request $request)
    {
        $request->validate([
            'q'           => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'letter'      => 'nullable|alpha|max:1',
            'sort'        => 'nullable|in:name,books,newest',
        ]);

        $query = Author::active()->withCount('primaryBooks');

        // Search
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nationality', 'like', "%{$search}%");
            });
        }

        // Filter by nationality
        if ($request->filled('nationality')) {
            $query->where('nationality', $request->input('nationality'));
        }

        // Letter filter (first letter of name)
        if ($request->filled('letter')) {
            $query->where('name', 'like', $request->input('letter') . '%');
        }

        // Sorting
        $sort = $request->input('sort', 'name');
        switch ($sort) {
            case 'books':
                $query->orderBy('primary_books_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        $authors = $query->paginate(24);

        // Get unique nationalities for filter
        $nationalities = Author::active()
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '')
            ->distinct()
            ->pluck('nationality')
            ->sort();

        // If AJAX request, return JSON
        if ($request->ajax()) {
            return response()->json([
                'authors' => $authors->items(),
                'total' => $authors->total(),
            ]);
        }

        return view('authors', compact('authors', 'nationalities'));
    }

    /**
     * Public: Show author profile page
     */
    public function publicShow($id)
    {
        $author = Author::active()->findOrFail($id);

        // Group books by author role (from pivot table)
        $primaryBooks = $author->booksByType('primary')->paginate(12, ['*'], 'page');
        $coAuthorBooks = $author->booksByType('co-author')->get();
        $translatedBooks = $author->booksByType('translator')->get();
        $editedBooks = $author->booksByType('editor')->get();
        $illustratedBooks = $author->booksByType('illustrator')->get();

        // Also include books linked via author_id FK that may not be in the pivot table
        $pivotBookIds = $primaryBooks->pluck('id')
            ->merge($coAuthorBooks->pluck('id'))
            ->merge($translatedBooks->pluck('id'))
            ->merge($editedBooks->pluck('id'))
            ->merge($illustratedBooks->pluck('id'));

        $primaryBooksViaFk = $author->primaryBooks()
            ->whereNotIn('id', $pivotBookIds)
            ->get();

        return view('author', compact(
            'author',
            'primaryBooks',
            'primaryBooksViaFk',
            'coAuthorBooks',
            'translatedBooks',
            'editedBooks',
            'illustratedBooks'
        ));
    }

    /**
     * Search authors (existing - used for dropdowns)
     */
    public function search(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100']);
        $query = $request->query('q', '');

        $authors = Author::where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'nationality')
            ->limit(10)
            ->get();

        return response()->json($authors);
    }

    /**
     * Admin authors page
     */
    public function index()
    {
        return view('Dashbord_Admin.authors');
    }

    /**
     * API: Get authors list with pagination, search, filters
     */
    public function getAuthorsApi(Request $request)
    {
        $request->validate([
            'search'     => 'nullable|string|max:100',
            'status'     => 'nullable|in:active,inactive',
            'api_status' => 'nullable|in:enriched,pending',
        ]);

        $query = Author::withCount('primaryBooks');

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nationality', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // API status filter
        if ($request->filled('api_status')) {
            $apiStatus = $request->input('api_status');
            if ($apiStatus === 'enriched') {
                $query->whereNotNull('api_id');
            } elseif ($apiStatus === 'pending') {
                $query->whereNull('api_id');
            }
        }

        $query->orderBy('name', 'asc');

        $authors = $query->paginate(15);

        // Stats
        $stats = [
            'total' => Author::count(),
            'active' => Author::where('status', 'active')->count(),
            'enriched' => Author::whereNotNull('api_id')->count(),
            'pending' => Author::whereNull('api_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $authors,
            'stats' => $stats,
        ]);
    }

    /**
     * API: Get single author details
     */
    public function show($id)
    {
        $author = Author::with(['primaryBooks' => function ($q) {
            $q->select('id', 'title', 'image', 'price', 'author_id');
        }])->withCount('primaryBooks')->findOrFail($id);

        // Book count by type from pivot
        $booksByType = BookAuthor::where('author_id', $id)
            ->select('author_type', DB::raw('count(*) as count'))
            ->groupBy('author_type')
            ->pluck('count', 'author_type')
            ->toArray();

        return response()->json([
            'success' => true,
            'author' => $author,
            'books_by_type' => $booksByType,
        ]);
    }

    /**
     * API: Update author
     */
    public function update(Request $request, $id)
    {
        $author = Author::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $author->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات المؤلف بنجاح',
            'author' => $author,
        ]);
    }

    /**
     * API: Delete author
     */
    public function destroy($id)
    {
        $author = Author::withCount('primaryBooks')->findOrFail($id);

        if ($author->primary_books_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن حذف المؤلف لأنه مرتبط بـ {$author->primary_books_count} كتاب",
            ], 400);
        }

        // Remove pivot entries
        BookAuthor::where('author_id', $id)->delete();
        $author->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المؤلف بنجاح',
        ]);
    }

    /**
     * API: Preview enrichment data from Open Library
     */
    public function previewEnrichment($id)
    {
        $author = Author::findOrFail($id);
        $service = new AuthorEnrichmentService();

        $data = $service->enrichAuthor($author);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على بيانات لهذا المؤلف. تحقق من سجل الأخطاء للتفاصيل.',
            ]);
        }

        return response()->json([
            'success' => true,
            'current' => $author->toArray(),
            'api_data' => $data,
        ]);
    }

    /**
     * API: Apply selected enrichment fields
     */
    public function applyEnrichment(Request $request, $id)
    {
        $author = Author::findOrFail($id);

        $request->validate([
            'fields'               => 'required|array|max:10',
            'fields.*'             => 'string|in:biography,birth_date,death_date,nationality,website,photo',
            'api_data'             => 'required|array',
            'api_data.biography'   => 'nullable|string|max:10000',
            'api_data.birth_date'  => 'nullable|date',
            'api_data.death_date'  => 'nullable|date',
            'api_data.nationality' => 'nullable|string|max:100',
            'api_data.website'     => 'nullable|url|max:500',
            'api_data.photo_url'   => 'nullable|url|max:500',
            'api_data.api_id'      => 'nullable|string|max:100',
        ]);

        $fields = $request->input('fields', []);
        $apiData = $request->input('api_data', []);

        if (empty($fields)) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم اختيار أي حقول للتحديث',
            ]);
        }

        $updated = [];

        foreach ($fields as $field) {
            switch ($field) {
                case 'biography':
                    if (isset($apiData['biography'])) {
                        $author->biography = $apiData['biography'];
                        $updated[] = 'السيرة الذاتية';
                    }
                    break;
                case 'birth_date':
                    if (isset($apiData['birth_date'])) {
                        $author->birth_date = $apiData['birth_date'];
                        $updated[] = 'تاريخ الميلاد';
                    }
                    break;
                case 'death_date':
                    if (isset($apiData['death_date'])) {
                        $author->death_date = $apiData['death_date'];
                        $updated[] = 'تاريخ الوفاة';
                    }
                    break;
                case 'nationality':
                    if (isset($apiData['nationality'])) {
                        $author->nationality = $apiData['nationality'];
                        $updated[] = 'الجنسية';
                    }
                    break;
                case 'website':
                    if (isset($apiData['website'])) {
                        $author->website = $apiData['website'];
                        $updated[] = 'الموقع';
                    }
                    break;
                case 'photo':
                    if (isset($apiData['photo_url'])) {
                        $service = new AuthorEnrichmentService();
                        $path = $service->downloadPhoto($apiData['photo_url'], $author);
                        if ($path) {
                            // Delete old image
                            if ($author->profile_image && \Storage::disk('public')->exists($author->profile_image)) {
                                \Storage::disk('public')->delete($author->profile_image);
                            }
                            $author->profile_image = $path;
                            $updated[] = 'الصورة';
                        }
                    }
                    break;
            }
        }

        $author->api_source = 'open_library';
        $author->api_id = $apiData['api_id'] ?? $author->api_id;
        $author->api_last_updated = now();
        $author->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث: ' . implode(', ', $updated),
            'author' => $author,
        ]);
    }

    /**
     * API: Import authors from books.author field
     */
    public function importFromBooks()
    {
        $service = new AuthorEnrichmentService();

        // Get unique author names from books that have no author_id
        $bookAuthors = Book::whereNull('author_id')
            ->whereNotNull('author')
            ->where('author', '!=', '')
            ->select('author')
            ->distinct()
            ->pluck('author');

        $created = 0;
        $linked = 0;
        $duplicates = [];

        foreach ($bookAuthors as $authorName) {
            $authorName = trim($authorName);
            if (empty($authorName)) {
                continue;
            }

            // Check if author already exists (exact match)
            $existingAuthor = Author::where('name', $authorName)->first();

            if ($existingAuthor) {
                // Link books to existing author
                $booksLinked = Book::where('author', $authorName)
                    ->whereNull('author_id')
                    ->update(['author_id' => $existingAuthor->id]);
                $linked += $booksLinked;
                continue;
            }

            // Check for similar authors (duplicate detection)
            $similar = $service->findSimilarAuthors($authorName);
            if (!empty($similar)) {
                $duplicates[] = [
                    'name' => $authorName,
                    'similar' => $similar,
                    'books_count' => Book::where('author', $authorName)->whereNull('author_id')->count(),
                ];
                continue;
            }

            // Create new author
            $newAuthor = Author::create([
                'name' => $authorName,
                'status' => 'active',
            ]);

            // Link books
            $booksLinked = Book::where('author', $authorName)
                ->whereNull('author_id')
                ->update(['author_id' => $newAuthor->id]);

            $created++;
            $linked += $booksLinked;
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'linked' => $linked,
            'duplicates' => $duplicates,
            'message' => "تم إنشاء {$created} مؤلف جديد وربط {$linked} كتاب",
        ]);
    }

    /**
     * API: Resolve a duplicate by merging or creating new
     */
    public function resolveDuplicate(Request $request)
    {
        $validated = $request->validate([
            'action'             => 'required|in:merge,create',
            'name'               => 'required|string|max:255',
            'existing_author_id' => 'required_if:action,merge|nullable|integer|exists:authors,id',
        ]);

        $action = $validated['action'];
        $bookAuthorName = $validated['name'];
        $existingAuthorId = $validated['existing_author_id'] ?? null;

        if ($action === 'merge' && $existingAuthorId) {
            $count = Book::where('author', $bookAuthorName)
                ->whereNull('author_id')
                ->update(['author_id' => $existingAuthorId]);

            return response()->json([
                'success' => true,
                'message' => "تم ربط {$count} كتاب بالمؤلف الموجود",
            ]);
        }

        if ($action === 'create') {
            $newAuthor = Author::create([
                'name' => $bookAuthorName,
                'status' => 'active',
            ]);

            $count = Book::where('author', $bookAuthorName)
                ->whereNull('author_id')
                ->update(['author_id' => $newAuthor->id]);

            return response()->json([
                'success' => true,
                'message' => "تم إنشاء المؤلف وربط {$count} كتاب",
            ]);
        }

        return response()->json(['success' => false, 'message' => 'إجراء غير صالح'], 400);
    }

    /**
     * API: Check for similar authors (duplicate detection)
     */
    public function checkDuplicates(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'exclude_id' => 'nullable|integer',
        ]);

        $service = new AuthorEnrichmentService();
        $similar = $service->findSimilarAuthors($validated['name'], $validated['exclude_id'] ?? null);

        return response()->json([
            'success' => true,
            'similar' => $similar,
        ]);
    }
}
