<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Book;
use App\Models\Author;
use App\Models\BookAuthor;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all books that don't have author_id set
        $books = Book::whereNull('author_id')->get();
        
        foreach ($books as $book) {
            // First, try to find existing author by name from the old 'author' field
            if (!empty($book->author)) {
                $author = Author::where('name', $book->author)->first();
                
                if (!$author) {
                    // Create new author if doesn't exist
                    $author = Author::create([
                        'name' => $book->author,
                        'status' => 'active'
                    ]);
                }
                
                // Update book with author_id
                $book->update(['author_id' => $author->id]);
                
                // Also create entry in book_authors table as primary author
                BookAuthor::updateOrCreate([
                    'book_id' => $book->id,
                    'author_id' => $author->id,
                    'author_type' => 'primary'
                ]);
            }
        }
        
        // Also handle books that might have entries in book_authors but no author_id
        $bookAuthors = BookAuthor::where('author_type', 'primary')
            ->whereHas('book', function($query) {
                $query->whereNull('author_id');
            })
            ->get();
            
        foreach ($bookAuthors as $bookAuthor) {
            $bookAuthor->book->update(['author_id' => $bookAuthor->author_id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all author_id to null
        Book::whereNotNull('author_id')->update(['author_id' => null]);
    }
};