<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register polymorphic morph map
        Relation::enforceMorphMap([
            'admin' => \App\Models\Admin::class,
            'user' => \App\Models\User::class,
            'merchant' => \App\Models\Merchant::class,
            'agent' => \App\Models\Agent::class,
        ]);

        \BezhanSalleh\LanguageSwitch\LanguageSwitch::configureUsing(function (\BezhanSalleh\LanguageSwitch\LanguageSwitch $switch) {
            $switch->locales(['ar', 'en']);
        });
    }
}
