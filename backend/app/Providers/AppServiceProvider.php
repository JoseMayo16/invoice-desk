<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Invoice;
use App\Payments\PaymentGateway;
use App\Payments\StripeGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function () {
            $secret = config('services.stripe.secret');

            return new StripeGateway($secret ? new StripeClient($secret) : null);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Route parameters resolve only inside the signed-in user's company, so
        // another company's id is a plain 404, never a 403 that confirms it exists.
        // Authentication runs before bindings, so the user is always known here.
        Route::bind('client', fn (string $id, $route) => Client::ownedBy(request()->user())->findOrFail($id));
        Route::bind('invoice', fn (string $id, $route) => Invoice::ownedBy(request()->user())->findOrFail($id));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}
