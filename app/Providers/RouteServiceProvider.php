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
        //
        // Slug is matched FIRST, even when the value is numeric: a slug can
        // legitimately be all digits (e.g. a book titled "005" → slug "005").
        // Checking is_numeric() first would treat that slug as an id and load
        // the wrong record (id=5 → "1984") or 404 when no such id exists. The
        // numeric id branch is only a fallback for legacy/internal links that
        // still pass $model->id, and only when no slug matches.
        $bindBySlugThenId = fn (string $model) => function ($v) use ($model) {
            return $model::where('slug', $v)->first()
                ?? (is_numeric($v) ? $model::findOrFail($v) : abort(404));
        };
        Route::bind('book', $bindBySlugThenId(\App\Models\Book::class));
        Route::bind('author', $bindBySlugThenId(\App\Models\Author::class));
        Route::bind('category', $bindBySlugThenId(\App\Models\Category::class));
        Route::bind('publisher', $bindBySlugThenId(\App\Models\PublishingHouse::class));
        Route::bind('series', $bindBySlugThenId(\App\Models\Series::class));

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
