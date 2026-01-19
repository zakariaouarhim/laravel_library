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

class PublisherController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->query('q');
        
        $publishers = PublishingHouse::where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'country')
            ->limit(10)
            ->get();
        
        return response()->json($publishers);
    }
}
