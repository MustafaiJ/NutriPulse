<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Travel Mode') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">
        @if ($active)
            <div class="bg-sky-50 border-l-4 border-sky-500 rounded-lg p-4">
                <p class="font-semibold text-sky-900">
                    ✈️ {{ __('Traveling now — diet plan paused') }} {{ $active->start_date->format('M j') }}–{{ $active->end_date->format('M j') }}
                </p>
                @if ($active->notes)
                    <p class="text-sm text-sky-700 mt-1">{{ $active->notes }}</p>
                @endif
            </div>
        @endif

        @if ($isAdmin)
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('Mark a travel period') }}</h3>
                <form method="POST" action="{{ route('travel.store') }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="start_date" :value="__('Start')" />
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date', today()->toDateString()) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div>
                            <x-input-label for="end_date" :value="__('End')" />
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date', today()->addDays(3)->toDateString()) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                    </div>
                    <div>
                        <x-input-label for="notes" :value="__('Notes (optional)')" />
                        <x-text-input id="notes" type="text" name="notes" :value="old('notes')"
                            placeholder="{{ __('e.g. Trip to Goa') }}" class="mt-1 block w-full" />
                    </div>
                    <x-primary-button class="bg-sky-600 hover:bg-sky-700">{{ __('Add Travel Period') }}</x-primary-button>
                </form>
            </div>
        @endif

        @if ($upcoming->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('Upcoming') }}</h3>
                <div class="divide-y divide-gray-100">
                    @foreach ($upcoming as $period)
                        <div class="py-2 flex items-center justify-between">
                            <div>
                                <span class="font-medium text-gray-800">
                                    {{ $period->start_date->format('M j') }} – {{ $period->end_date->format('M j, Y') }}
                                </span>
                                @if ($period->notes)
                                    <span class="text-sm text-gray-500">· {{ $period->notes }}</span>
                                @endif
                            </div>
                            @if ($isAdmin)
                                <form method="POST" action="{{ route('travel.destroy', $period) }}" onsubmit="return confirm('{{ __('Remove?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($periods->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('All travel periods') }}</h3>
                <div class="divide-y divide-gray-100">
                    @foreach ($periods as $period)
                        <div class="py-2 flex items-center justify-between">
                            <div>
                                <span class="font-medium text-gray-800">
                                    {{ $period->start_date->format('M j, Y') }} – {{ $period->end_date->format('M j, Y') }}
                                </span>
                                @if ($period->notes)
                                    <span class="text-sm text-gray-500">· {{ $period->notes }}</span>
                                @endif
                            </div>
                            @if ($isAdmin)
                                <form method="POST" action="{{ route('travel.destroy', $period) }}" onsubmit="return confirm('{{ __('Remove?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p class="text-center text-gray-500 py-8">
                {{ $isAdmin ? __('No travel periods yet.') : __('No travel periods marked.') }}
            </p>
        @endif
    </div>
</x-app-layout>