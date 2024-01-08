<?php

namespace Chrysanthos\PulseRequests\Recorders;

use Carbon\CarbonImmutable;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Laravel\Pulse\Pulse;

class RequestRecorder
{
    public array $listen = [
        RequestHandled::class,
    ];

    public function __construct(
        protected Pulse $pulse,
        protected Repository $config
    ) {
        //
    }

    public function record(RequestHandled $event)
    {
        $timestamp = CarbonImmutable::now()->getTimestamp();

        $this->pulse->lazy(function () use ($timestamp, $event) {
            $statusCode = $event->response->getStatusCode();

            $this->pulse->record(
                type: match (true) {
                    $statusCode >= 0 && $statusCode < 200 => 'informational',
                    $statusCode >= 200 && $statusCode < 300 => 'successful',
                    $statusCode >= 300 && $statusCode < 400 => 'redirection',
                    $statusCode >= 400 && $statusCode < 500 => 'client_error',
                    default => 'server_error',
                },
                key: 'request',
                //key: "{$connection}:{$queue}",
                timestamp: $timestamp,
            )->count()->onlyBuckets();
        });
    }
}
