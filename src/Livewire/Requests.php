<?php

namespace Chrysanthos\PulseRequests\Livewire;

use Chrysanthos\PulseRequests\Recorders\RequestRecorder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Livewire;

class Requests extends Card
{
    public function render()
    {
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
        [$requests, $time, $runAt] = $this->remember(fn () => $this->graph(
            ['informational', 'successful', 'redirection', 'client_error', 'server_error'],
            'count',
        ));

        $requestCollection = $requests['request'] ?? collect();

        if (Livewire::isLivewireRequest()) {
            $this->dispatch('requests-chart-update', requests: $requestCollection);
        }

        return View::make('requests::livewire.requests', [
            'requests' => $requestCollection,
            'time' => $time,
            'runAt' => $runAt,
            'config' => Config::get('pulse.recorders.'.RequestRecorder::class),
        ]);
    }
}
