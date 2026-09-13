<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Diet Plan') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex justify-end">
            @if (auth()->user()->isDietitian())
                <a href="{{ route('diet-plans.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                    + {{ __('New Diet Plan') }}
                </a>
            @endif
        </div>

        @if ($current)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $current->title }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ __('By') }} {{ $current->createdBy->name }} · {{ __('active from') }} {{ $current->active_from?->format('M j, Y') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($current->target_calories)
                            <span class="inline-flex items-center px-3 py-1 rounded-lg bg-green-100 text-green-800 text-sm font-semibold">
                                {{ $current->target_calories }} kcal
                            </span>
                        @endif
                        @if ($current->target_macros)
                            <span class="text-sm text-gray-600">
                                P {{ $current->target_macros['protein'] ?? '-' }}g ·
                                C {{ $current->target_macros['carbs'] ?? '-' }}g ·
                                F {{ $current->target_macros['fat'] ?? '-' }}g
                            </span>
                        @endif
                    </div>
                </div>
                <div class="mt-4 prose prose-sm max-w-none text-gray-700 whitespace-pre-line">{{ $current->content }}</div>
                @if ($current->notes)
                    <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                        {{ $current->notes }}
                    </div>
                @endif
                @if (auth()->user()->isDietitian())
                    <div class="flex gap-3 mt-4">
                        <a href="{{ route('diet-plans.edit', $current) }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('diet-plans.destroy', $current) }}" onsubmit="return confirm('{{ __('Delete this plan?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                        </form>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                <p class="text-gray-500">{{ __('No active diet plan yet.') }}</p>
                @if (auth()->user()->isDietitian())
                    <a href="{{ route('diet-plans.create') }}" class="inline-block mt-3 text-sm text-green-600 hover:text-green-800">
                        {{ __('Create the first plan →') }}
                    </a>
                @endif
            </div>
        @endif

        @if ($plans->count() > 1)
            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('Past plans') }}</h3>
                <div class="divide-y divide-gray-100">
                    @foreach ($plans->skip(1) as $plan)
                        <div class="py-3">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-medium text-gray-800">{{ $plan->title }}</span>
                                    <span class="text-sm text-gray-500">· {{ $plan->active_from?->format('M j, Y') ?? 'no start date' }}</span>
                                    @if ($plan->target_calories)
                                        <span class="text-xs text-gray-500">· {{ $plan->target_calories }} kcal</span>
                                    @endif
                                </div>
                                @if (auth()->user()->isDietitian())
                                    <div class="flex gap-2 shrink-0">
                                        <a href="{{ route('diet-plans.edit', $plan) }}" class="text-sm text-blue-600 hover:text-blue-800">{{ __('Edit') }}</a>
                                        <form method="POST" action="{{ route('diet-plans.destroy', $plan) }}" onsubmit="return confirm('{{ __('Delete?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $plan->content }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>