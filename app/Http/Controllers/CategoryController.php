<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Category;


class CategoryController extends Controller
{
    
    public function index()
    {
        $categorie= category::whereNull('parent_id')->with('children')->get();

    // Fetch categories with their counts
    
    
    return view('categories', compact('categorie'));
    }
    
}
