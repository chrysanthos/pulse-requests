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
            <x-pulse::icons.rocket-launch/>
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
            <x-pulse::no-results/>
        @else
            <div class="grid gap-3 mx-px mb-px">
                <div wire:key="requests-graph">
                    <div class="mt-3 relative">
                        <div
                                wire:ignore
                                x-data="requestChart({
                                    readings: @js($requests),
                                    sampleRate: {{ $config['sample_rate'] ?? 1 }},
                                })"
                        >
                            <canvas x-ref="canvas"
                                    class="ring-1 ring-gray-900/5 dark:ring-gray-100/10 bg-gray-50 dark:bg-gray-800 rounded-md shadow-sm h-52"></canvas>
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
            let baseDataset = (color, fromColor, toColor) => {
                return {
                    borderCapStyle: 'round',
                    pointHitRadius: 20,
                    pointRadius: 0,
                    tension: 0.2,
                    borderWidth: 1,
                    fill: true,
                    hover: {
                        mode: 'nearest'
                    },
                    pointHoverRadius: 3,
                    borderColor: color,
                    pointHoverBackgroundColor: color,
                    backgroundColor: (context) => {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;

                        if (!chartArea) {
                            // This case happens on initial chart load
                            return;
                        }

                        return getGradient(ctx, chartArea, fromColor, toColor);
                    },
                }
            }

            let chart = new Chart(
                this.$refs.canvas,
                {
                    type: "line",
                    data: {
                        labels: this.labels(config.readings[Object.keys(config.readings)[0]]),
                        datasets: [
                            {
                                label: "Informational",
                                data: this.scale(config.readings.informational),
                                ...baseDataset('#5B91FC7F', '#5B91FC7F', 'rgba(156,180,241,0.1)')
                            },
                            {
                                label: "Successful",
                                data: this.scale(config.readings.successful),
                                order: 1,
                                ...baseDataset('#8EE3B7FF', 'rgba(142,227,183,0.2)', 'rgba(220,255,235,0.1)')
                            },
                            {
                                label: "Redirection",
                                data: this.scale(config.readings.redirection),
                                order: 2,
                                ...baseDataset('#eab308', '#eab308', 'rgba(255,234,167,0.1)')
                            },
                            {
                                label: "Client Error",
                                data: this.scale(config.readings.client_error),
                                order: 3,
                                ...baseDataset('#882de7', '#882de7', 'rgba(136,45,231,0.27)')
                            },
                            {
                                label: "Server Error",
                                data: this.scale(config.readings.server_error),
                                order: 4,
                                ...baseDataset('#e11d48', '#e11d48', 'rgba(250,78,112,0.1)')
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

            function getGradient(ctx, chartArea, fromColor, toColor) {
                let width, height, gradient;
                const chartWidth = chartArea.right - chartArea.left;
                const chartHeight = chartArea.bottom - chartArea.top;
                if (!gradient || width !== chartWidth || height !== chartHeight) {
                    // Create the gradient because this is either the first
                    // render or the size of the chart has changed
                    width = chartWidth;
                    height = chartHeight;
                    gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                    gradient.addColorStop(1, fromColor);
                    gradient.addColorStop(0, toColor);
                }
                return gradient;
            }

            Livewire.on("requests-chart-update", ({requests}) => {
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
