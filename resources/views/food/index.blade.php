<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Food Log') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <form method="GET" action="{{ route('food.index') }}" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}"
                    class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                <x-primary-button class="bg-green-600 hover:bg-green-700">{{ __('Go') }}</x-primary-button>
            </form>
            <div class="flex items-center gap-2">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('food.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            + {{ __('Log Food') }}
                        </a>
                    @endif
                @endauth
                <span class="text-sm text-gray-600">
                    {{ __('Calories today:') }} <strong>{{ number_format($dailyCalories) }}</strong>
                </span>
            </div>
        </div>

        @if ($entries->isEmpty())
            <div class="mt-8 text-center py-12 text-gray-500">
                {{ __('No food entries for this date.') }}
            </div>
        @else
            <div class="mt-6 space-y-4">
                @foreach ($entries as $entry)
                    <div class="bg-white rounded-xl shadow-sm p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 capitalize">
                                        {{ $entry->meal_type }}
                                    </span>
                                    <span class="text-sm text-gray-500">{{ $entry->logged_at->format('H:i') }}</span>
                                </div>
                                <p class="mt-1 text-gray-800">{{ $entry->description }}</p>
                                @if ($entry->portion)
                                    <p class="text-sm text-gray-500">{{ __('Portion:') }} {{ $entry->portion }}</p>
                                @endif
                                @if ($entry->getDisplayCalories())
                                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-semibold text-gray-800">
                                            {{ number_format($entry->getDisplayCalories()) }} kcal
                                        </span>
                                        @if ($entry->corrected_calories)
                                            <span class="text-xs text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">
                                                {{ __('corrected') }} (AI: {{ number_format($entry->ai_calories) }})
                                            </span>
                                        @elseif ($entry->ai_calories)
                                            <span class="text-xs text-gray-400">{{ __('AI estimate') }}</span>
                                        @endif
                                        @if ($macros = $entry->getDisplayMacros())
                                            <span class="text-xs text-gray-500">
                                                P {{ $macros['protein'] }}g · C {{ $macros['carbs'] }}g · F {{ $macros['fat'] }}g
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('food.edit', $entry) }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('food.destroy', $entry) }}" onsubmit="return confirm('{{ __('Delete this entry?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        @if ($entry->comments->isNotEmpty())
                            <div class="mt-3 space-y-2">
                                @foreach ($entry->comments as $comment)
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-sm text-gray-800">{{ $comment->comment }}</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (auth()->user()->isDietitian())
                            <form method="POST" action="{{ route('food.comment', $entry) }}" class="mt-3">
                                @csrf
                                <div class="flex gap-2">
                                    <input type="text" name="comment" placeholder="{{ __('Add a comment…') }}"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                        required>
                                    <x-primary-button class="bg-blue-600 hover:bg-blue-700 whitespace-nowrap">{{ __('Comment') }}</x-primary-button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-8">
            <h3 class="font-semibold text-gray-800 mb-3">{{ __('Last 7 days') }}</h3>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="grid grid-cols-7 gap-2" style="grid-template-rows: 120px">
                    @foreach (range(6, 0) as $offset)
                        @php
                            $day = today()->subDays($offset);
                            $key = $day->toDateString();
                            $calories = $weeklyCalories->get($key, 0);
                            $max = max($weeklyCalories->max() ?? 1, 1);
                            $height = $calories > 0 ? max(10, intval($calories / $max * 110)) : 4;
                        @endphp
                        <div class="flex flex-col items-center justify-end gap-1">
                            <span class="text-[10px] text-gray-500 font-medium">{{ number_format($calories) }}</span>
                            <div class="w-full max-w-md rounded-t-md bg-green-500" style="height: {{ $height }}px"></div>
                            <span class="text-[10px] text-gray-500">{{ $day->format('D') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>