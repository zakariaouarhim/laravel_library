<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\BookAuthor;
use App\Models\PublishingHouse;
use App\Models\Category;
use App\Services\AuthorEnrichmentService;
use App\Services\Seo\MetaBuilder;
use App\Services\Seo\SchemaBuilder;
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

        // Alias rows (other-script names of a canonical author) are hidden from
        // the listing; their books are counted on the canonical row instead.
        $query = Author::active()->canonicalOnly()->withCount(['primaryBooks', 'aliasBooks']);

        // Search
        if ($request->filled('q')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nationality', 'like', "%{$search}%")
                  ->orWhereHas('aliases', function ($alias) use ($search) {
                      $alias->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('nameVariants', function ($variant) use ($search) {
                      $variant->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('aliases.nameVariants', function ($variant) use ($search) {
                      $variant->where('name', 'like', "%{$search}%");
                  });
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
                $query->orderByRaw('(primary_books_count + alias_books_count) desc');
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

        $seo = app(MetaBuilder::class)->forStatic(
            'المؤلفون - مكتبة الفقراء',
            'تصفح جميع المؤلفين المتوفرين في مكتبة الفقراء. اكتشف كتبهم وسيرهم الذاتية.',
            route('authors.index')
        );
        if ($authors->currentPage() > 1) {
            $seo['robots']    = 'noindex,follow';
            $seo['canonical'] = route('authors.index');
        }

        return view('authors', compact('authors', 'nationalities', 'seo'));
    }

    /**
     * Public: Show author profile page
     */
    public function publicShow(\App\Models\Author $author)
    {
        // Route-model binding already loaded by slug — re-fetch with active() scope to 404 inactive.
        $author = \App\Models\Author::active()->whereKey($author->id)->firstOrFail();

        // Alias rows (same author in another script) 301 to the canonical page.
        // If the canonical row is inactive/deleted, render the alias standalone.
        if ($author->isAlias()) {
            $canonical = \App\Models\Author::active()->find($author->canonical_author_id);
            if ($canonical) {
                return redirect()->route('author.show', $canonical, 301);
            }
        }

        // All author ids of this group (the row itself + its alias rows), so
        // books attached to any script's row appear on the one author page.
        $groupIds = $author->groupIds()->all();

        // Alias-row names + string spelling variants, for "also known as".
        $alsoKnownAs = $author->allNames()
            ->reject(fn ($name) => $name === $author->name)
            ->values();

        // Non-primary roles come from the pivot only (these are tagged in book_authors).
        $booksByTypeForGroup = function (string $type) use ($groupIds) {
            $ids = \Illuminate\Support\Facades\DB::table('book_authors')
                ->whereIn('author_id', $groupIds)
                ->where('author_type', $type)
                ->pluck('book_id');

            return \App\Models\Book::whereIn('id', $ids)
                ->with('bundles:id,title,price,image')
                ->get();
        };
        $coAuthorBooks    = $booksByTypeForGroup('co-author');
        $translatedBooks  = $booksByTypeForGroup('translator');
        $editedBooks      = $booksByTypeForGroup('editor');
        $illustratedBooks = $booksByTypeForGroup('illustrator');

        // "Primary" books come from BOTH the pivot (book_authors with
        // author_type='primary') AND the books.author_id foreign key. Union the IDs
        // and paginate the combined set — paginating only the pivot side and tacking
        // the FK books on top of every page would make pagination meaningless.
        $pivotPrimaryIds = \Illuminate\Support\Facades\DB::table('book_authors')
            ->whereIn('author_id', $groupIds)
            ->where('author_type', 'primary')
            ->pluck('book_id');

        $fkPrimaryIds = \App\Models\Book::query()
            ->standardOnly()
            ->whereIn('author_id', $groupIds)
            ->pluck('id');

        $primaryBookIds = $pivotPrimaryIds->merge($fkPrimaryIds)->unique()->values();

        $primaryBooks = \App\Models\Book::query()
            ->whereIn('id', $primaryBookIds)
            ->with(['primaryAuthor', 'bundles:id,title,price,image'])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->paginate(12, ['*'], 'page');

        $seo = app(MetaBuilder::class)->forAuthor($author);

        $schemaBuilder = app(SchemaBuilder::class);
        $trail = [
            ['label' => 'الرئيسية',  'url' => url('/')],
            ['label' => 'المؤلفون', 'url' => route('authors.index')],
            ['label' => $author->name],
        ];
        $schemas = [
            'person'      => $schemaBuilder->forAuthor($author),
            'breadcrumbs' => $schemaBuilder->forBreadcrumbs($trail),
            // Only emit ItemList when there are primary books on the current page.
            'itemList'    => $primaryBooks->count() > 0
                ? $schemaBuilder->forItemList(collect($primaryBooks->items()), ($primaryBooks->currentPage() - 1) * $primaryBooks->perPage() + 1)
                : null,
        ];

        return view('author', compact(
            'author',
            'alsoKnownAs',
            'primaryBooks',
            'coAuthorBooks',
            'translatedBooks',
            'editedBooks',
            'illustratedBooks',
            'seo',
            'schemas'
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

        $query = Author::withCount(['primaryBooks', 'aliases'])
            ->with('canonical:id,name');

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

        // Stats — single query with conditional aggregation
        $raw = Author::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN api_id IS NOT NULL THEN 1 ELSE 0 END) as enriched,
            SUM(CASE WHEN api_id IS NULL THEN 1 ELSE 0 END) as pending
        ")->first();

        $stats = [
            'total' => $raw->total,
            'active' => (int) $raw->active,
            'enriched' => (int) $raw->enriched,
            'pending' => (int) $raw->pending,
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
        }, 'nameVariants'])->withCount('primaryBooks')->findOrFail($id);

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
            // SEO overrides: leave blank to fall back to MetaBuilder auto-generation.
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            // Replace the old image (same authors/ folder used by enrichment downloads).
            if ($author->profile_image && \Storage::disk('public')->exists($author->profile_image)) {
                \Storage::disk('public')->delete($author->profile_image);
            }
            $validated['profile_image'] = $request->file('profile_image')->store('authors', 'public');
        } else {
            // No new upload — leave the existing image untouched.
            unset($validated['profile_image']);
        }

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

        // Soft delete bypasses the FK's nullOnDelete, so aliases would be
        // silently orphaned — require unlinking first.
        if (Author::where('canonical_author_id', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المؤلف لأن لديه أسماء بديلة مرتبطة — فك الربط أولاً',
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
     * API: Link an author as an alias of another (canonical) author.
     * Same real-world author written in another script/language.
     */
    public function linkCanonical(Request $request, $id)
    {
        $request->validate([
            'canonical_id' => 'required|integer|exists:authors,id',
        ]);

        $alias = Author::findOrFail($id);
        $target = Author::findOrFail($request->input('canonical_id'));

        // Always link to the group's root so chains never form.
        $root = $target->groupRoot();

        if ($root->id === $alias->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن ربط المؤلف بنفسه أو بأحد أسمائه البديلة',
            ], 400);
        }

        DB::transaction(function () use ($alias, $root) {
            // Flatten: re-point the alias's own aliases to the new root.
            Author::where('canonical_author_id', $alias->id)
                ->update(['canonical_author_id' => $root->id]);

            $alias->forceFill(['canonical_author_id' => $root->id])->save();
        });

        // Re-index the whole group's books so each gains the other names.
        Book::whereIn('author_id', $root->groupIds())->searchable();

        return response()->json([
            'success' => true,
            'message' => "تم ربط \"{$alias->name}\" كاسم بديل لـ \"{$root->name}\"",
        ]);
    }

    /**
     * API: Unlink an alias author back to a standalone author.
     */
    public function unlinkCanonical($id)
    {
        $alias = Author::findOrFail($id);

        if (!$alias->isAlias()) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المؤلف ليس اسماً بديلاً',
            ], 400);
        }

        // Capture the group BEFORE clearing, so both sides get re-indexed.
        $groupIds = $alias->groupIds();

        $alias->forceFill(['canonical_author_id' => null])->save();

        Book::whereIn('author_id', $groupIds)->searchable();

        return response()->json([
            'success' => true,
            'message' => "تم فك ربط \"{$alias->name}\"",
        ]);
    }

    /**
     * API: Add a plain-string spelling variant to an author (no author row
     * of its own, e.g. "البير كامي"). Feeds search via allNames().
     */
    public function addName(Request $request, $id)
    {
        $author = Author::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($validated['name']);

        if ($name === '' || $name === $author->name) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الاسم هو اسم المؤلف نفسه',
            ], 400);
        }

        $variant = $author->nameVariants()->firstOrCreate(['name' => $name]);

        // Re-index the whole group's books so they gain the new spelling.
        Book::whereIn('author_id', $author->groupIds())->searchable();

        return response()->json([
            'success' => true,
            'message' => "تمت إضافة الاسم البديل \"{$name}\"",
            'variant' => $variant,
        ]);
    }

    /**
     * API: Remove a spelling variant.
     */
    public function deleteName($id, $nameId)
    {
        $author = Author::findOrFail($id);
        $variant = $author->nameVariants()->findOrFail($nameId);

        $variant->delete();

        Book::whereIn('author_id', $author->groupIds())->searchable();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الاسم البديل',
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
            'api_data.api_source'  => 'nullable|string|max:50',
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

        $author->api_source = $apiData['api_source'] ?? $author->api_source ?? 'unknown';
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
