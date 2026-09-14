<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto space-y-3">
            @forelse ($notifications as $notification)
                <a href="{{ route('diet-plans.index') }}"
                    class="block bg-white rounded-xl shadow-sm p-4 {{ $notification->read_at ? '' : 'border-l-4 border-green-500' }}">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-gray-800">{{ $notification->data['message'] ?? $notification->type }}</p>
                        @if (! $notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" onclick="event.stopPropagation()">
                                @csrf
                                <button type="submit" class="text-xs text-green-700 hover:text-green-900 shrink-0">{{ __('Mark read') }}</button>
                            </form>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
                    {{ __('No notifications yet.') }}
                </div>
            @endforelse

            @if ($notifications->hasPages())
                <div class="mt-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>