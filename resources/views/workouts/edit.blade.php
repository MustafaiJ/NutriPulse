<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Workout') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('workouts.update', $workout) }}"
                    x-data="{ type: '{{ $workout->type }}' }">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <div class="mt-1 grid grid-cols-4 gap-2">
                            @foreach (\App\Models\Workout::TYPES as $wtype)
                                <label class="flex flex-col items-center cursor-pointer">
                                    <input type="radio" name="type" value="{{ $wtype }}" class="peer sr-only"
                                        @checked($workout->type === $wtype) @click="type = '{{ $wtype }}'">
                                    <span class="w-full text-center px-2 py-2.5 rounded-lg border text-xs capitalize
                                        peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600
                                        border-gray-300 text-gray-700 bg-white">
                                        {{ $wtype }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div x-show="type === 'strength'" class="mt-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <x-input-label for="exercise" :value="__('Exercise')" />
                                <x-text-input id="exercise" type="text" name="exercise"
                                    :value="old('exercise', $workout->exercise)" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="sets" :value="__('Sets')" />
                                <x-text-input id="sets" type="number" min="1" name="sets"
                                    :value="old('sets', $workout->details_json['sets'] ?? null)" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="reps" :value="__('Reps')" />
                                <x-text-input id="reps" type="number" min="1" name="reps"
                                    :value="old('reps', $workout->details_json['reps'] ?? null)" class="mt-1 block w-full" />
                            </div>
                            <div class="col-span-2">
                                <x-input-label for="weight_kg" :value="__('Weight (kg)')" />
                                <x-text-input id="weight_kg" type="number" step="0.5" min="0" name="weight_kg"
                                    :value="old('weight_kg', $workout->details_json['weight_kg'] ?? null)" class="mt-1 block w-full" />
                            </div>
                        </div>
                    </div>

                    <div x-show="type !== 'strength'" class="mt-4">
                        <x-input-label for="distance_km" :value="__('Distance (km)')" />
                        <x-text-input id="distance_km" type="number" step="0.1" min="0" name="distance_km"
                            :value="old('distance_km', $workout->details_json['distance_km'] ?? null)" class="mt-1 block w-full" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="duration_minutes" :value="__('Duration (min)')" />
                        <x-text-input id="duration_minutes" type="number" min="1" name="duration_minutes"
                            :value="old('duration_minutes', $workout->duration_minutes)" class="mt-1 block w-full" />
                    </div>

                    <div class="flex items-center justify-end gap-2 mt-4">
                        <a href="{{ route('workouts.index') }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('Cancel') }}</a>
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>