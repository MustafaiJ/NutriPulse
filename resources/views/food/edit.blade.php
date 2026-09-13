<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Food Entry') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                @php
                    $macros = $entry->getDisplayMacros();
                    $aiMacros = $entry->ai_macros;
                @endphp

                <div class="mb-4">
                    <p class="text-sm text-gray-500">{{ $entry->logged_at->format('F j, Y H:i') }}</p>
                    <p class="font-medium text-gray-800">{{ $entry->description }}</p>
                    @if ($entry->ai_calories)
                        <div class="mt-2 rounded-lg bg-gray-50 p-3 text-sm">
                            <span class="text-gray-500">{{ __('AI estimated:') }}</span>
                            <span class="font-semibold text-gray-800">{{ number_format($entry->ai_calories) }} kcal</span>
                            @if ($aiMacros)
                                <span class="text-gray-500">
                                    (P {{ $aiMacros['protein'] }}g · C {{ $aiMacros['carbs'] }}g · F {{ $aiMacros['fat'] }}g)
                                </span>
                            @endif
                            @if ($entry->corrected_calories)
                                <div class="mt-1 text-amber-600">{{ __('Corrected by you:', ) }} {{ number_format($entry->corrected_calories) }} kcal</div>
                            @endif
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('food.update', $entry) }}">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="meal_type" :value="__('Meal type')" />
                        <select id="meal_type" name="meal_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            @foreach ($meals as $meal)
                                <option value="{{ $meal }}" @selected($entry->meal_type === $meal)>{{ ucfirst($meal) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('meal_type')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="description" :value="__('Description')" />
                        <x-text-input id="description" type="text" name="description" :value="old('description', $entry->description)" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="portion" :value="__('Portion size')" />
                        <x-text-input id="portion" type="text" name="portion" :value="old('portion', $entry->portion)" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('portion')" class="mt-2" />
                    </div>

                    <div class="mt-6 rounded-lg bg-green-50 border border-green-200 p-4">
                        <h3 class="font-semibold text-sm text-green-900 mb-3">{{ __('Correct the estimate (optional)') }}</h3>
                        <div class="grid grid-cols-4 gap-3">
                            <div class="col-span-4 sm:col-span-1">
                                <x-input-label for="corrected_calories" :value="__('Calories (kcal)')" />
                                <x-text-input id="corrected_calories" type="number" min="0" name="corrected_calories"
                                    :value="old('corrected_calories', $entry->corrected_calories)" class="mt-1 block w-full" />
                            </div>
                            @foreach (['protein' => 'Protein (g)', 'carbs' => 'Carbs (g)', 'fat' => 'Fat (g)'] as $key => $label)
                                <div>
                                    <x-input-label for="corrected_macros_{{ $key }}" :value="__($label)" />
                                    <x-text-input id="corrected_macros_{{ $key }}" type="number" min="0"
                                        name="corrected_macros[{{ $key }}]"
                                        :value="old('corrected_macros.'.$key, $macros[$key] ?? null)"
                                        class="mt-1 block w-full" />
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-green-800 mt-2">
                            {{ __('Leave blank to keep the AI estimate.') }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 mt-4">
                        <a href="{{ route('food.index') }}" class="text-sm text-gray-600 hover:text-gray-800">{{ __('Cancel') }}</a>
                        <x-primary-button class="bg-green-600 hover:bg-green-700">
                            {{ __('Save Changes') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>