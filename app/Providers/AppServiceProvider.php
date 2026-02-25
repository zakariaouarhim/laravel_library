<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Category;
use App\Models\Book;
use App\View\Composers\AdminSidebarComposer;
use App\Observers\BookObserver;
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

        Book::observe(BookObserver::class);

        View::composer('header', function ($view) {
           $navCategories = Cache::remember('header_nav_categories', 3600, function () {
               return Category::parentWithChildren()->get();
           });
           $view->with('navCategories', $navCategories);
       });

       View::composer('Dashbord_Admin.Sidebar', AdminSidebarComposer::class);

       View::composer(['components.book-carousel', 'partials.book-card-grid', 'moredetail', 'moredetail2'], function ($view) {
           static $wishlistBookIds = null;
           static $followedAuthorIds = null;
           static $followedPublisherIds = null;

           if ($wishlistBookIds === null) {
               if (auth()->check()) {
                   $wishlistBookIds = auth()->user()->wishlist()->pluck('book_id')->toArray();

                   $userFollows = \App\Models\Follow::where('user_id', auth()->id())->get();
                   $followedAuthorIds = $userFollows->where('followable_type', 'author')
                       ->pluck('followable_id')->toArray();
                   $followedPublisherIds = $userFollows->where('followable_type', 'publisher')
                       ->pluck('followable_id')->toArray();
               } else {
                   $wishlistBookIds = session()->get('wishlist', []);
                   $followedAuthorIds = [];
                   $followedPublisherIds = [];
               }
           }
           $view->with('wishlistBookIds', $wishlistBookIds);
           $view->with('followedAuthorIds', $followedAuthorIds);
           $view->with('followedPublisherIds', $followedPublisherIds);
       });
    }
}
