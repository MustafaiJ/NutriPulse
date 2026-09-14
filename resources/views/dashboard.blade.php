<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Today's stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Calories today') }}</div>
                <div class="mt-1 text-2xl font-bold {{ $calorieDelta > 150 ? 'text-amber-600' : 'text-gray-800' }}">
                    {{ number_format($todayCalories) }}
                </div>
                <div class="text-xs text-gray-500">/ {{ number_format($targetCalories) }} kcal target</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Sugar (7d avg)') }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-800">{{ $avgSugar7d ?? '--' }} <span class="text-sm font-normal text-gray-500">mg/dL</span></div>
                <div class="text-xs {{ ($sugarInRange7d ?? 100) < 60 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $sugarInRange7d ?? 0 }}% in range
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Gym streak') }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-800">{{ $workoutStreak }} <span class="text-sm font-normal text-gray-500">{{ __('days') }}</span></div>
                <div class="text-xs text-gray-500">{{ $workoutCount7d }} workouts in 7d</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Action') }}</div>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('food.create') }}" class="block text-green-700 text-sm font-medium">+ {{ __('Log food') }}</a>
                    <a href="{{ route('blood-sugar.index') }}" class="block text-green-700 text-sm font-medium">+ {{ __('Log sugar') }}</a>
                    <a href="{{ route('workouts.index') }}" class="block text-green-700 text-sm font-medium">+ {{ __('Log workout') }}</a>
                </div>
            </div>
        </div>

        <!-- AI Insight -->
        <div class="bg-gradient-to-br from-green-50 to-white rounded-xl shadow-sm p-5 border border-green-200">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <span>✨</span> {{ __('AI Insight') }}
                </h3>
                <button type="button" onclick="refreshInsight()"
                    class="text-xs text-green-700 hover:text-green-900 font-medium">
                    {{ __('Refresh') }}
                </button>
            </div>
            <p id="ai-insight" class="text-gray-700 leading-relaxed">
                @if ($latestInsight)
                    {{ $latestInsight->insight_text }}
                    <span class="block text-xs text-gray-400 mt-2">{{ $latestInsight->generated_at->diffForHumans() }} · {{ $latestInsight->source }}</span>
                @else
                    {{ __('Generating your first insight…') }}
                @endif
            </p>
            <div id="ai-insight-loading" class="hidden text-sm text-gray-400">Generating…</div>
        </div>

        <!-- Feature shortcuts -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
            <a href="{{ route('food.index') }}" class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                <div class="text-2xl mb-1">🍽️</div>
                <h3 class="font-semibold text-gray-800">Food Log</h3>
                <p class="text-sm text-gray-500">Meals & calories</p>
            </a>
            <a href="{{ route('blood-sugar.index') }}" class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                <div class="text-2xl mb-1">🩸</div>
                <h3 class="font-semibold text-gray-800">Blood Sugar</h3>
                <p class="text-sm text-gray-500">Readings & trends</p>
            </a>
            <a href="{{ route('workouts.index') }}" class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                <div class="text-2xl mb-1">🏋️</div>
                <h3 class="font-semibold text-gray-800">Gym Log</h3>
                <p class="text-sm text-gray-500">Workouts & progress</p>
            </a>
            <a href="{{ route('diet-plans.index') }}" class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                <div class="text-2xl mb-1">🥗</div>
                <h3 class="font-semibold text-gray-800">Diet Plan</h3>
                <p class="text-sm text-gray-500">Current plan & targets</p>
            </a>
            <a href="{{ route('travel.index') }}" class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                <div class="text-2xl mb-1">✈️</div>
                <h3 class="font-semibold text-gray-800">Travel Mode</h3>
                <p class="text-sm text-gray-500">Mark travel dates</p>
            </a>
            <a href="{{ route('users.index') }}" class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                <div class="text-2xl mb-1">👥</div>
                <h3 class="font-semibold text-gray-800">Users</h3>
                <p class="text-sm text-gray-500">Manage dietitian access</p>
            </a>
        </div>
    </div>

    @push('scripts')
        <script>
            function refreshInsight() {
                const text = document.getElementById('ai-insight');
                const loading = document.getElementById('ai-insight-loading');
                text.classList.add('hidden');
                loading.classList.remove('hidden');

                fetch('{{ route('dashboard.insight') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                .then(r => r.json())
                .then((data) => {
                    text.innerHTML = data.insight;
                    loading.classList.add('hidden');
                    text.classList.remove('hidden');
                })
                .catch(() => {
                    loading.textContent = 'Could not refresh right now.';
                });
            }
        </script>
    @endpush
</x-app-layout>