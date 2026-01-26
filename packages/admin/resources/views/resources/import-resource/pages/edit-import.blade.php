<x-filament::page>
    @if ($record->status === \Lunar\Models\Excel\Import::STATUS_IN_PROGRESS
        || $record->status === \Lunar\Models\Excel\Import::STATUS_PENDING)
        <div
            wire:poll.3s
            class="space-y-4"
        >
            <x-filament::card>
                <div class="text-sm text-gray-500">
                    Status
                </div>

                <div class="text-lg font-semibold">
                    {{ \Lunar\Models\Excel\Import::$statuses[$record->status] ?? 'Unknown' }}
                </div>

                <div class="mt-4">
                    <div class="text-sm text-gray-500 mb-1">
                        Progress: {{ $record->progress }}
                    </div>

                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-primary-600 h-2 rounded-full transition-all"
                        ></div>
                    </div>
                </div>
            </x-filament::card>
        </div>
    @else
        <x-filament::card>
            <div class="text-lg font-semibold text-green-600">
                {{ \Lunar\Models\Excel\Import::$statuses[$record->status] ?? 'Unknown' }}
            </div>

            <div class="mt-2 text-sm text-gray-500">
                Final progress: {{ $record->progress }}
            </div>
        </x-filament::card>
    @endif
</x-filament::page>
