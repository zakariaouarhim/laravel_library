<?php

namespace App\Providers;

use App\Models\Book_Review;
use App\Models\Order;
use App\Models\Quote;
use App\Models\UserModel;
use App\Policies\OrderPolicy;
use App\Policies\QuotePolicy;
use App\Policies\ReviewPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Book_Review::class => ReviewPolicy::class,
        Quote::class       => QuotePolicy::class,
        Order::class       => OrderPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Admins bypass all policy checks. Returning null falls through to the
        // policy method; returning true short-circuits with allow.
        Gate::before(function (UserModel $user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });
    }
}
