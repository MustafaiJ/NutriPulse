<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gym Log') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Quick stats -->
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ $workouts->count() }}</div>
                <div class="text-xs text-gray-500">{{ __('Workouts') }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">
                    {{ $totalDuration ? intdiv($totalDuration, 60).'h '.($totalDuration % 60).'m' : '--' }}
                </div>
                <div class="text-xs text-gray-500">{{ __('Total time') }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ $streak }}</div>
                <div class="text-xs text-gray-500">{{ __('day streak') }}</div>
            </div>
        </div>

        <!-- Quick add -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <h3 class="font-semibold text-gray-800 mb-3">{{ __('Log a workout') }}</h3>
            <form method="POST" action="{{ route('workouts.store') }}" class="space-y-3"
                x-data="{ type: 'strength' }">
                @csrf

                <div>
                    <x-input-label for="type" :value="__('Type')" />
                    <div class="mt-1 grid grid-cols-4 gap-2">
                        @foreach (\App\Models\Workout::TYPES as $wtype)
                            <label class="flex flex-col items-center cursor-pointer">
                                <input type="radio" name="type" value="{{ $wtype }}" class="peer sr-only"
                                    @checked($loop->first) @click="type = '{{ $wtype }}'">
                                <span class="w-full text-center px-2 py-2.5 rounded-lg border text-xs capitalize
                                    peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600
                                    border-gray-300 text-gray-700 bg-white">
                                    {{ $wtype }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div x-show="type === 'strength'">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <x-input-label for="exercise" :value="__('Exercise')" />
                            <x-text-input id="exercise" type="text" name="exercise" list="exercise-list"
                                placeholder="{{ __('e.g. Bench Press') }}" class="mt-1 block w-full" />
                            <datalist id="exercise-list">
                                @foreach ($byExercise->keys() as $name)
                                    <option value="{{ $name }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <x-input-label for="sets" :value="__('Sets')" />
                            <x-text-input id="sets" type="number" min="1" name="sets" value="{{ old('sets') }}" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="reps" :value="__('Reps')" />
                            <x-text-input id="reps" type="number" min="1" name="reps" value="{{ old('reps') }}" class="mt-1 block w-full" />
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="weight_kg" :value="__('Weight (kg)')" />
                            <x-text-input id="weight_kg" type="number" step="0.5" min="0" name="weight_kg" value="{{ old('weight_kg') }}" class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>

                <div x-show="type !== 'strength'">
                    <x-input-label for="distance_km" :value="__('Distance (km)')" />
                    <x-text-input id="distance_km" type="number" step="0.1" min="0" name="distance_km" value="{{ old('distance_km') }}" class="mt-1 block w-full" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="duration_minutes" :value="__('Duration (min)')" />
                        <x-text-input id="duration_minutes" type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes', 30) }}" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="logged_at" :value="__('When?')" />
                        <input type="datetime-local" id="logged_at" name="logged_at"
                            value="{{ old('logged_at', now()->format('Y-m-d\TH:i')) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button class="w-full justify-center bg-green-600 hover:bg-green-700">
                        {{ __('Save Workout') }}
                    </x-primary-button>
                    <div class="flex gap-1 shrink-0">
                        @foreach ([7, 30, 90] as $range)
                            <a href="{{ route('workouts.index', ['days' => $range]) }}"
                                class="px-2 py-1 text-xs rounded-md {{ $days === $range ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                {{ $range }}d
                            </a>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>

        <!-- Progress charts -->
        @if ($byExercise->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('Strength progress (weight kg)') }}</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    @foreach ($byExercise->take(4) as $exercise => $rows)
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $exercise }}</h4>
                            <canvas class="progress-chart w-full" height="140"
                                data-chart='@json([
                                    'labels' => $rows->pluck('date'),
                                    'values' => $rows->pluck('weight'),
                                ])'></canvas>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- History -->
        <div class="bg-white rounded-xl shadow-sm p-4">
            <h3 class="font-semibold text-gray-800 mb-3">{{ __('History') }}</h3>
            @if ($workouts->isEmpty())
                <p class="text-center text-gray-500 py-6">{{ __('No workouts in the selected period.') }}</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($workouts as $workout)
                        <div class="py-2 flex items-center justify-between gap-2">
                            <div>
                                <span class="font-semibold text-gray-800 capitalize">{{ $workout->type }}</span>
                                @if ($workout->exercise)
                                    <span class="text-gray-700">· {{ $workout->exercise }}</span>
                                @endif
                                <div class="text-sm text-gray-500">
                                    @if ($workout->type === 'strength')
                                        {{ $workout->details_json['sets'] ?? '-' }} × {{ $workout->details_json['reps'] ?? '-' }} @
                                        {{ $workout->details_json['weight_kg'] ?? '-' }} kg
                                    @else
                                        {{ $workout->details_json['distance_km'] ?? '-' }} km
                                    @endif
                                    @if ($workout->duration_minutes) · {{ $workout->duration_minutes }} min @endif
                                    · {{ $workout->logged_at->format('M j, H:i') }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('workouts.edit', $workout) }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('workouts.destroy', $workout) }}" onsubmit="return confirm('{{ __('Delete?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof window.Chart === 'undefined') return;

                document.querySelectorAll('.progress-chart').forEach((canvas) => {
                    const data = JSON.parse(canvas.dataset.chart);

                    new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.values,
                                borderColor: '#16a34a',
                                backgroundColor: 'rgba(22,163,74,0.08)',
                                pointRadius: 4,
                                tension: 0.3,
                                fill: true,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { ticks: { font: { size: 10 } } },
                                x: { ticks: { font: { size: 9 }, maxTicksLimit: 6 } },
                            },
                        },
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>