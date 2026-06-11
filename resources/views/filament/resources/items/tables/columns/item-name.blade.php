<div class="min-w-0 py-1">
    <div class="truncate text-sm font-bold text-gray-950 dark:text-white">
        {{ $record->name }}
    </div>
    <div class="mt-1 truncate text-xs font-semibold text-gray-500 dark:text-gray-400">
        SKU: {{ $record->sku }} | {{ $record->defaultUnit?->code ?? '-' }}
    </div>
</div>
