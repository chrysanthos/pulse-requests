@php
    use Illuminate\Support\Str;
@endphp
<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Requests"
        title="Time: {{ number_format($time) }}ms; Run at: {{ $runAt }};"
        details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.rocket-launch />
        </x-slot:icon>
        <x-slot:actions>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 font-medium">
                    <div class="h-0.5 w-3 rounded-full bg-[rgba(107,114,128,0.5)]"></div>
                    Informational
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 font-medium">
                    <div class="h-0.5 w-3 rounded-full bg-[rgba(147,51,234,0.5)]"></div>
                    Successful
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 font-medium">
                    <div class="h-0.5 w-3 rounded-full bg-[#9333ea]"></div>
                    Redirection
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 font-medium">
                    <div class="h-0.5 w-3 rounded-full bg-[#eab308]"></div>
                    Client Error
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 font-medium">
                    <div class="h-0.5 w-3 rounded-full bg-[#e11d48]"></div>
                    Server Error
                </div>
            </div>
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($requests->isEmpty())
            <x-pulse::no-results />
        @else
            <div class="grid gap-3 mx-px mb-px">
                <div wire:key="requests-graph">
                    <div class="mt-3 relative">
                        <div
                            wire:ignore
                            class="h-full"
                            x-data="requestChart({
                                    readings: @js($requests),
                                    sampleRate: {{ $config['sample_rate'] ?? 1 }},
                                })"
                        >
                            <canvas x-ref="canvas"
                                    class="ring-1 ring-gray-900/5 dark:ring-gray-100/10 bg-gray-50 dark:bg-gray-800 rounded-md shadow-sm"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>

@script
<script>
    Alpine.data("requestChart", (config) => ({
        init() {
            let chart = new Chart(
                this.$refs.canvas,
                {
                    type: "line",
                    data: {
                        labels: this.labels(config.readings[Object.keys(config.readings)[0]]),
                        datasets: [
                            {
                                label: "Informational",
                                borderColor: "rgba(107,114,128,0.5)",
                                data: this.scale(config.readings.informational),
                                order: 0
                            },
                            {
                                label: "Successful",
                                borderColor: "rgba(147,51,234,0.5)",
                                data: this.scale(config.readings.successful),
                                order: 1
                            },
                            {
                                label: "Redirection",
                                borderColor: "#eab308",
                                data: this.scale(config.readings.redirection),
                                order: 2
                            },
                            {
                                label: "Client Error",
                                borderColor: "#9333ea",
                                data: this.scale(config.readings.client_error),
                                order: 3
                            },
                            {
                                label: "Server Error",
                                borderColor: "#e11d48",
                                data: this.scale(config.readings.server_error),
                                order: 4
                            }
                        ]
                    },
                    options: {
                        maintainAspectRatio: false,
                        layout: {
                            autoPadding: false,
                            padding: {
                                top: 1
                            }
                        },
                        datasets: {
                            line: {
                                borderWidth: 2,
                                borderCapStyle: "round",
                                pointHitRadius: 10,
                                pointStyle: false,
                                tension: 0.2,
                                spanGaps: false,
                                segment: {
                                    borderColor: (ctx) => ctx.p0.raw === 0 && ctx.p1.raw === 0 ? "transparent" : undefined
                                }
                            }
                        },
                        scales: {
                            x: {
                                display: false
                            },
                            y: {
                                display: false,
                                min: 0,
                                max: this.highest(config.readings)
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: "index",
                                position: "nearest",
                                intersect: false,
                                callbacks: {
                                    beforeBody: (context) => context
                                        .map(item => `${item.dataset.label}: ${config.sampleRate < 1 ? "~" : ""}${item.formattedValue}`)
                                        .join(", "),
                                    label: () => null
                                }
                            }
                        }
                    }
                }
            )

            Livewire.on("requests-chart-update", ({ requests }) => {
                if (chart === undefined) {
                    return
                }

                if (requests === undefined && chart) {
                    chart.destroy()
                    chart = undefined
                    return
                }

                chart.data.labels = this.labels(requests[Object.keys(requests)[0]])
                chart.options.scales.y.max = this.highest(requests)
                chart.data.datasets[0].data = this.scale(requests.informational)
                chart.data.datasets[1].data = this.scale(requests.successful)
                chart.data.datasets[2].data = this.scale(requests.redirection)
                chart.data.datasets[3].data = this.scale(requests.client_error)
                chart.data.datasets[4].data = this.scale(requests.server_error)
                chart.update()
            })
        },
        labels(readings) {
            return Object.keys(readings)
        },
        scale(data) {
            return Object.values(data).map(value => value * (1 / config.sampleRate))
        },
        highest(readings) {
            return Math.max(...Object.values(readings).map(dataset => Math.max(...Object.values(dataset)))) * (1 / config.sampleRate)
        }
    }))
</script>
@endscript
