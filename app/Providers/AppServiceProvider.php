<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Category;
use App\View\Composers\AdminSidebarComposer;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
       Schema::defaultStringLength(191);

       View::composer('header', function ($view) {
           $navCategories = Cache::remember('header_nav_categories', 3600, function () {
               return Category::whereNull('parent_id')
                   ->with('children')
                   ->get();
           });
           $view->with('navCategories', $navCategories);
       });

       View::composer('Dashbord_Admin.Sidebar', AdminSidebarComposer::class);

       View::composer(['components.book-carousel', 'partials.book-card-grid'], function ($view) {
           static $wishlistBookIds = null;
           if ($wishlistBookIds === null) {
               if (auth()->check()) {
                   $wishlistBookIds = auth()->user()->wishlist()->pluck('book_id')->toArray();
               } else {
                   $wishlistBookIds = session()->get('wishlist', []);
               }
           }
           $view->with('wishlistBookIds', $wishlistBookIds);
       });
    }
}
