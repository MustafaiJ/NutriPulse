<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('food.index') }}" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="text-2xl mb-2">🍽️</div>
                <h3 class="font-semibold text-gray-800">Food Log</h3>
                <p class="text-sm text-gray-500">Review food entries and add comments.</p>
            </a>
            <a href="{{ route('blood-sugar.index') }}" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="text-2xl mb-2">🩸</div>
                <h3 class="font-semibold text-gray-800">Blood Sugar</h3>
                <p class="text-sm text-gray-500">Readings and trend charts.</p>
            </a>
            <a href="{{ route('diet-plans.index') }}" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="text-2xl mb-2">🥗</div>
                <h3 class="font-semibold text-gray-800">Diet Plan</h3>
                <p class="text-sm text-gray-500">Create and manage the diet plan.</p>
            </a>
        </div>
    </div>
</x-app-layout>