<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Log Food') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('food.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="meal_type" :value="__('Meal type')" />
                        <div class="mt-1 grid grid-cols-4 gap-2">
                            @foreach ($meals as $meal)
                                <label class="flex flex-col items-center cursor-pointer">
                                    <input type="radio" name="meal_type" value="{{ $meal }}" class="peer sr-only"
                                        @if (old('meal_type', $loop->first ? 'breakfast' : null) === $meal || old('meal_type') === $meal) checked @endif
                                        required>
                                    <span class="w-full text-center px-2 py-2.5 rounded-lg border text-sm capitalize
                                        peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600
                                        border-gray-300 text-gray-700 bg-white">
                                        {{ $meal }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('meal_type')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="description" :value="__('What did you eat?')" />
                        <textarea id="description" name="description" rows="2" required autofocus
                            placeholder="{{ __('e.g. 2 chapatis, dal, small bowl rice') }}"
                            class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-base">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="portion" :value="__('Portion size (optional)')" />
                        <x-text-input id="portion" type="text" name="portion" :value="old('portion')"
                            placeholder="{{ __('e.g. 1 bowl, 2 roti, medium plate') }}"
                            class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('portion')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="logged_at" :value="__('When?')" />
                        <input type="datetime-local" id="logged_at" name="logged_at"
                            value="{{ old('logged_at', now()->format('Y-m-d\TH:i')) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                        <x-input-error :messages="$errors->get('logged_at')" class="mt-2" />
                    </div>

                    <div class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                        {{ __('Calories & macros are estimated by AI from your description. You can adjust them after saving.') }}
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button class="bg-green-600 hover:bg-green-700">
                            {{ __('Save & Estimate') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>