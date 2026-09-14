<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Blood Sugar') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Quick stats -->
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold {{ $latest && ! $latest->isInRange() ? 'text-red-600' : 'text-gray-800' }}">
                    {{ $latest ? $latest->value : '--' }}
                </div>
                <div class="text-xs text-gray-500">{{ __('Latest') }} @if ($latest) {{ $latest->unit }} @endif</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ $todayCount }}</div>
                <div class="text-xs text-gray-500">{{ __('Today') }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold {{ $outOfRangeCount > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $outOfRangeCount }}
                </div>
                <div class="text-xs text-gray-500">{{ __('Out of range') }}</div>
            </div>
        </div>

        <!-- Quick add form -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <h3 class="font-semibold text-gray-800 mb-3">{{ __('Quick log') }}</h3>
            <form method="POST" action="{{ route('blood-sugar.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="value" :value="__('Reading')" />
                        <div class="flex items-center gap-2 mt-1">
                            <input type="number" step="0.1" id="value" name="value" required
                                placeholder="100" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                        </div>
                        <x-input-error :messages="$errors->get('value')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="unit" :value="__('Unit')" />
                        <select id="unit" name="unit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            @foreach (\App\Models\BloodSugarReading::UNITS as $unit)
                                <option value="{{ $unit }}" @selected($unit === 'mg/dL')>{{ $unit }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="context_tag" :value="__('Context')" />
                    <div class="mt-1 grid grid-cols-4 gap-2">
                        @foreach (\App\Models\BloodSugarReading::CONTEXTS as $context)
                            <label class="flex flex-col items-center cursor-pointer">
                                <input type="radio" name="context_tag" value="{{ $context }}" class="peer sr-only"
                                    @if ($loop->first) checked @endif required>
                                <span class="w-full text-center px-2 py-2.5 rounded-lg border text-xs capitalize
                                    peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600
                                    border-gray-300 text-gray-700 bg-white">
                                    {{ str_replace('_', ' ', $context) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('context_tag')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="logged_at" :value="__('When?')" />
                    <input type="datetime-local" id="logged_at" name="logged_at"
                        value="{{ old('logged_at', now()->format('Y-m-d\TH:i')) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    <x-input-error :messages="$errors->get('logged_at')" class="mt-1" />
                </div>
                <x-primary-button class="w-full justify-center bg-green-600 hover:bg-green-700">
                    {{ __('Save Reading') }}
                </x-primary-button>
            </form>
        </div>

        <!-- Trend chart -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-800">{{ __('Trend') }}</h3>
                <form method="GET" action="{{ route('blood-sugar.index') }}" class="flex gap-1">
                    @foreach ([7, 14, 30, 90] as $range)
                        <button type="submit" name="days" value="{{ $range }}"
                            class="px-2 py-1 text-xs rounded-md {{ $days === $range ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $range }}d
                        </button>
                    @endforeach
                </form>
            </div>

            @if ($readings->isNotEmpty())
                <canvas id="sugarChart" height="220" class="w-full"
                    data-chart='@js([
                        'labels' => $readings->map(fn ($r) => $r->logged_at->format('M j H:i')),
                        'values' => $readings->map(fn ($r) => $r->value),
                        'inRange' => $readings->map(fn ($r) => $r->isInRange() ? 'rgba(34,197,94,0.8)' : 'rgba(239,68,68,0.8)'),
                    ])'></canvas>
                <p class="text-xs text-gray-500 mt-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>{{ __('In range') }}
                    <span class="inline-block w-2 h-2 rounded-full bg-red-500 mx-1 ml-3"></span>{{ __('Out of range') }}
                    @if ($latest && $latest->unit === 'mmol/L') · {{ __('shown in mmol/L') }} @endif
                </p>
            @else
                <p class="text-center text-gray-500 py-8">{{ __('No readings in the selected period.') }}</p>
            @endif
        </div>

        <!-- Recent readings -->
        @if ($readings->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('History') }}</h3>
                <div class="divide-y divide-gray-100">
                    @foreach ($readings->reverse() as $reading)
                        <div class="py-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="inline-block w-2.5 h-2.5 rounded-full {{ $reading->isInRange() ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                <div>
                                    <span class="font-semibold text-gray-800">{{ $reading->value }} {{ $reading->unit }}</span>
                                    <span class="text-sm text-gray-500 capitalize">· {{ str_replace('_', ' ', $reading->context_tag) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">{{ $reading->logged_at->format('M j, H:i') }}</span>
                                @if (auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('blood-sugar.destroy', $reading) }}" onsubmit="return confirm('{{ __('Delete?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">&times;</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const canvas = document.getElementById('sugarChart');
                if (!canvas || typeof window.Chart === 'undefined') return;

                const data = JSON.parse(canvas.dataset.chart);

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Blood sugar',
                            data: data.values,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22,163,74,0.08)',
                            pointBackgroundColor: data.inRange,
                            pointRadius: 5,
                            tension: 0.3,
                            fill: true,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            y: {
                                ticks: {
                                    font: { size: 10 },
                                },
                            },
                            x: {
                                ticks: {
                                    font: { size: 10 },
                                    maxTicksLimit: 8,
                                },
                            },
                        },
                    },
                });
            });
        </script>
    @endpush
</x-app-layout>