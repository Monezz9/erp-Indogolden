@php
    $stock = (float) ($record->stock_qty ?? 0);
    $minimum = (float) ($record->minimum_stock ?? 0);
    $unit = $record->defaultUnit?->code ?? '';
    $status = \App\Filament\Resources\Items\Tables\ItemsTable::stockStatus($record);
    $progress = \App\Filament\Resources\Items\Tables\ItemsTable::stockProgress($record);
    $statusClasses = match ($status) {
        'Aman' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-200 dark:border-emerald-900',
        'Perhatian' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-200 dark:border-amber-900',
        default => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-200 dark:border-red-900',
    };
    $barClasses = match ($status) {
        'Aman' => 'bg-emerald-500',
        'Perhatian' => 'bg-amber-500',
        default => 'bg-red-500',
    };
@endphp

<div class="min-w-[180px] py-1">
    <div class="flex items-center justify-between gap-3">
        <div class="text-sm font-black text-gray-950 dark:text-white">
            {{ \App\Support\IndoNumber::decimal($stock) }} {{ $unit }}
        </div>
        <span class="hidden rounded-full border px-2 py-0.5 text-[11px] font-bold sm:inline-flex {{ $statusClasses }}">
            {{ $status }}
        </span>
    </div>
    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
        <div class="h-full rounded-full {{ $barClasses }}" style="width: {{ $progress }}%"></div>
    </div>
    <div class="mt-1 text-[11px] font-semibold text-gray-500">
        Min {{ \App\Support\IndoNumber::decimal($minimum) }} {{ $unit }}
    </div>
</div>
