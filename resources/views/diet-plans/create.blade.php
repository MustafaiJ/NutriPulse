<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Diet Plan') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('diet-plans.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" type="text" name="title" :value="old('title')"
                            placeholder="{{ __('e.g. Maintenance plan — Sept') }}" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="content" :value="__('Plan details')" />
                        <textarea id="content" name="content" rows="8" required
                            placeholder="{{ __('Meal structure, foods to include/avoid, timings…') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">{{ old('content') }}</textarea>
                        <x-input-error :messages="$errors->get('content')" class="mt-2" />
                    </div>

                    <div class="mt-4 rounded-lg bg-green-50 border border-green-200 p-4">
                        <h3 class="font-semibold text-sm text-green-900 mb-3">{{ __('Targets (optional)') }}</h3>
                        <div class="grid grid-cols-4 gap-3">
                            <div class="col-span-4 sm:col-span-1">
                                <x-input-label for="target_calories" :value="__('Calories')" />
                                <x-text-input id="target_calories" type="number" min="500" name="target_calories"
                                    :value="old('target_calories')" class="mt-1 block w-full" />
                            </div>
                            @foreach (['protein' => 'Protein (g)', 'carbs' => 'Carbs (g)', 'fat' => 'Fat (g)'] as $key => $label)
                                <div>
                                    <x-input-label for="target_macros_{{ $key }}" :value="__($label)" />
                                    <x-text-input id="target_macros_{{ $key }}" type="number" min="0"
                                        name="target_macros[{{ $key }}]" :value="old('target_macros.'.$key)"
                                        class="mt-1 block w-full" />
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-green-800 mt-2">{{ __('Optional. Adds a target calorie/macro marker to the plan.') }}</p>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="active_from" :value="__('Active from')" />
                        <input type="date" id="active_from" name="active_from" :value="old('active_from', today()->toDateString())"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                        <x-input-error :messages="$errors->get('active_from')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="notes" :value="__('Notes (optional)')" />
                        <x-text-input id="notes" type="text" name="notes" :value="old('notes')"
                            placeholder="{{ __('e.g. Follow for 4 weeks, review in Oct') }}" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button class="bg-green-600 hover:bg-green-700">
                            {{ __('Publish Plan') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>