@php
    $labels = [
        'draft' => 'Rascunho',
        'pending' => 'Pendente',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
    ];
@endphp

<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.5s="">
    <x-pulse::card-header name="Documentos por Status"
        details="{{ $total }} no total · {{ number_format($completionRate, 1) }}% concluídos">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            <tbody>
                @foreach ($statuses as $status => $count)
                    <tr>
                        <td class="py-2 text-sm text-gray-700 dark:text-gray-300">{{ $labels[$status] ?? $status }}</td>
                        <td class="py-2 text-right text-sm font-bold text-gray-700 dark:text-gray-300">{{ number_format($count) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-pulse::scroll>
</x-pulse::card>
