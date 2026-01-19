<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\BookAuthor;
use App\Models\PublishingHouse;
use App\Models\Category;
use App\Services\BookService;
use App\Services\APIService;

class AuthorController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->query('q');
        
        $authors = Author::where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'nationality')
            ->limit(10)
            ->get();
        
        return response()->json($authors);
    }
}
