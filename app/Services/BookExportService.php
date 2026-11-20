<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;

/**
 * Streams the admin product catalogue out as CSV.
 *
 * Built for handing the catalogue to another platform (a marketplace, a social
 * catalogue, another shop), which is why the cover column carries an absolute
 * http(s) URL on our own domain rather than a filename: the destination fetches
 * the image over the web. Covers under public/images are served without any
 * hot-link restriction, so those URLs work from anywhere.
 *
 * Same shape as DashboardReportService::streamOrdersCsv — a closure for
 * response()->stream(), a UTF-8 BOM so Excel reads the Arabic, and chunked
 * reads so the row count never drives memory.
 */
class BookExportService
{
    /** enum books.status -> what the admin reads in the file. */
    private const STATUS_LABELS = [
        'active'       => 'نشط',
        'inactive'     => 'غير نشط',
        'discontinued' => 'متوقف',
        'out_of_stock' => 'غير متوفر',
    ];

    private const HEADERS = [
        'المعرف',
        'العنوان',
        'المؤلف',
        'ISBN',
        'دار النشر',
        'السلسلة',
        'رقم المجلد',
        'الفئات',
        'اللغة',
        'عدد الصفحات',
        'السعر',
        'نسبة الخصم',
        'السعر النهائي',
        'الكمية',
        'متوفر',
        'الحالة',
        'النوع',
        'رابط الصورة',
        'رابط المنتج',
        'الوصف',
    ];

    /**
     * Only the columns the file needs, plus the foreign keys the eager loads
     * resolve through. Deliberately narrow: cost_price and profit_margin are
     * our purchase cost and margin and must never be hydrated into a payload
     * that leaves the server, and the api_* / rewrite_* columns expose how the
     * catalogue is sourced. Same discipline as the sitemap query and
     * BookSearchService::formatForDropdown.
     */
    private const COLUMNS = [
        'id', 'slug', 'title', 'author_id', 'publishing_house_id', 'series_id',
        'volume_number', 'isbn', 'language', 'page_num', 'price', 'discount',
        'quantity', 'status', 'type', 'product_type', 'image', 'description',
    ];

    /**
     * @param  Builder  $query  A filtered Book query with no ordering applied
     *                          (chunkById imposes its own order by id).
     */
    public function streamBooksCsv(Builder $query): \Closure
    {
        return function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, self::HEADERS);

            $query->select(self::COLUMNS)
                ->with(['primaryAuthor:id,name', 'publishingHouse:id,name', 'series:id,name', 'categories:id,name'])
                // chunkById, not chunk: chunk pages by OFFSET, so any write
                // during a long export shifts later pages and rows get skipped
                // or repeated. chunkById walks the primary key instead.
                ->chunkById(500, function ($books) use ($handle) {
                    foreach ($books as $book) {
                        fputcsv($handle, $this->row($book));
                    }
                    flush();
                });

            fclose($handle);
        };
    }

    /** One book -> one CSV line, in HEADERS order. */
    private function row(Book $book): array
    {
        $price    = (float) $book->price;
        $discount = (float) $book->discount;

        return [
            $book->id,
            $book->title,
            $book->primaryAuthor->name ?? '',
            $book->isbn ?? '',
            $book->publishingHouse->name ?? '',
            $book->series->name ?? '',
            $book->volume_number ?? '',
            $book->categories->pluck('name')->implode(' / '),
            Book::LANGUAGE_LABELS[$book->language] ?? ($book->language ?? ''),
            $book->page_num ?? '',
            number_format($price, 2, '.', ''),
            number_format($discount, 2, '.', ''),
            number_format(round($price * (1 - $discount / 100), 2), 2, '.', ''),
            $book->quantity ?? 0,
            ($book->quantity ?? 0) > 0 ? 'نعم' : 'لا',
            self::STATUS_LABELS[$book->status] ?? ($book->status ?? ''),
            $this->typeLabel($book),
            $this->imageUrl($book),
            route('moredetail2.page', $book),
            $book->description ?? '',
        ];
    }

    /**
     * Absolute URL of the 400px cover, or an empty cell.
     *
     * Guarded on purpose, and not via $book->thumbnail / $book->large_image:
     * when `image` is empty those accessors return the string
     * 'images/book-placeholder.png', which does not exist on disk — the export
     * would hand the other platform a URL that 404s. An empty cell is honest.
     */
    private function imageUrl(Book $book): string
    {
        return filled($book->image) ? asset($book->image) : '';
    }

    private function typeLabel(Book $book): string
    {
        if ($book->product_type === 'bundle') {
            return 'حزمة';
        }

        return $book->type === 'accessory' ? 'ملحق' : 'كتاب';
    }
}
