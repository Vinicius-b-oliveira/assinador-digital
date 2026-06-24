<?php

namespace App\Providers;

use App\Models\User;
use App\Pulse\Cards\DatabaseHealth;
use App\Pulse\Cards\DocumentsByStatus;
use App\Pulse\Cards\SignaturesCollected;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        Vite::prefetch(concurrency: 3);

        Gate::define('viewPulse', fn (User $user): bool => (bool) $user->is_admin);

        Livewire::component('pulse.database-health', DatabaseHealth::class);
        Livewire::component('pulse.documents-by-status', DocumentsByStatus::class);
        Livewire::component('pulse.signatures-collected', SignaturesCollected::class);
    }
}
