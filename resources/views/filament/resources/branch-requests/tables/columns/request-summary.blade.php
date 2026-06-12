<div class="min-w-0 py-1">
    <div class="truncate text-sm font-bold text-slate-950 dark:text-white">
        {{ $record->request_number }}
    </div>
    <div class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">
        <x-filament::icon icon="heroicon-o-clock" class="h-3.5 w-3.5" />
        <span>{{ $record->request_date?->format('d M Y') ?? '-' }}</span>
    </div>
</div>
