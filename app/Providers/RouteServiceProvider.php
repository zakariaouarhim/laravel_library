<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        Route::model('review', \App\Models\Book_Review::class);

        // Dual route-model bindings — accept either the slug (preferred, SEO)
        // or the numeric id (legacy callers still passing $model->id to route()).
        // Registered here, not in routes/web.php, because route:cache bypasses
        // the Route::bind() calls in the web file: the cached routes file only
        // stores the routes themselves, not the closure-based binders. Without
        // this, browsing to /كتاب/<slug> after route:cache would 404 because
        // implicit model binding tries Book::where('id', '<slug>')->first().
        Route::bind('book', fn ($v) => is_numeric($v)
            ? \App\Models\Book::findOrFail($v)
            : \App\Models\Book::where('slug', $v)->firstOrFail()
        );
        Route::bind('author', fn ($v) => is_numeric($v)
            ? \App\Models\Author::findOrFail($v)
            : \App\Models\Author::where('slug', $v)->firstOrFail()
        );
        Route::bind('category', fn ($v) => is_numeric($v)
            ? \App\Models\Category::findOrFail($v)
            : \App\Models\Category::where('slug', $v)->firstOrFail()
        );
        Route::bind('publisher', fn ($v) => is_numeric($v)
            ? \App\Models\PublishingHouse::findOrFail($v)
            : \App\Models\PublishingHouse::where('slug', $v)->firstOrFail()
        );
        Route::bind('series', fn ($v) => is_numeric($v)
            ? \App\Models\Series::findOrFail($v)
            : \App\Models\Series::where('slug', $v)->firstOrFail()
        );

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
