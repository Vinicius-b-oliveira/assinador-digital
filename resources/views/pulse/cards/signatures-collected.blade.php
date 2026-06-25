@php
    $max = max($perDay->max('total'), 1);
@endphp

<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.5s="">
    <x-pulse::card-header name="Assinaturas Coletadas"
        details="{{ $total }} nos últimos 7 dias">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        <div class="flex flex-col gap-2">
            @foreach ($perDay as $day)
                <div class="flex items-center gap-3">
                    <span class="w-10 text-xs text-gray-400 dark:text-gray-600">{{ $day['label'] }}</span>
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-full rounded-full bg-purple-500 dark:bg-purple-400"
                            style="width: {{ $day['total'] > 0 ? max(round(($day['total'] / $max) * 100), 4) : 0 }}%"></div>
                    </div>
                    <span class="w-6 text-right text-sm font-bold text-gray-700 dark:text-gray-300">{{ $day['total'] }}</span>
                </div>
            @endforeach
        </div>
    </x-pulse::scroll>
</x-pulse::card>
