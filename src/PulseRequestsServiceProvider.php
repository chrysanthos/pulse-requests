<?php

namespace Chrysanthos\PulseRequests;

use Chrysanthos\PulseRequests\Livewire\Requests;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Livewire\LivewireManager;

class PulseRequestsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'requests');

        $this->callAfterResolving('livewire', function (LivewireManager $livewire, Application $app) {
            $livewire->component('requests', Requests::class);
        });
    }
}