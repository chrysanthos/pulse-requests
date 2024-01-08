<?php

namespace Chrysanthos\PulseRequests\Livewire;

use Chrysanthos\PulseRequests\RequestRecorder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Laravel\Pulse\Livewire\Card;
use Livewire\Livewire;

class Requests extends Card
{
    public function render()
    {
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
        [$requests, $time, $runAt] = $this->remember(fn() => $this->graph(
            ['informational', 'successful', 'redirection', 'client_error', 'server_error'],
            'count',
        ));

        if (Livewire::isLivewireRequest()) {
            $this->dispatch('requests-chart-update', requests: $requests['request']);
        }

        return View::make('requests::livewire.requests', [
            'requests' => $requests['request'],
            'time' => $time,
            'runAt' => $runAt,
            'config' => Config::get('pulse.recorders.' . RequestRecorder::class),
        ]);
    }
}
