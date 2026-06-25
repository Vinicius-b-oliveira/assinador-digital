<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.5s="">
    <x-pulse::card-header name="Saúde do Banco">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75" />
            </svg>
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $health['online'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <span class="text-lg font-bold text-gray-700 dark:text-gray-300">
                    {{ $health['online'] ? 'Online' : 'Indisponível' }}
                </span>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Conexão: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $health['connection'] }}</span>
            </p>

            @if ($health['online'])
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Latência: <span class="font-medium text-gray-700 dark:text-gray-300">{{ number_format($health['latency'], 2) }} ms</span>
                </p>
            @endif
        </div>
    </x-pulse::scroll>
</x-pulse::card>
