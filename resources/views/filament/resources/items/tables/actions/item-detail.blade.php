<div class="space-y-4">
    <div>
        <div class="text-lg font-black text-gray-950 dark:text-white">{{ $record->name }}</div>
        <div class="mt-1 text-sm font-semibold text-gray-500">{{ $record->sku }}</div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs font-bold uppercase text-gray-500">Kategori</div>
            <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ $record->category?->name ?? '-' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs font-bold uppercase text-gray-500">Satuan</div>
            <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ $record->defaultUnit?->code ?? '-' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs font-bold uppercase text-gray-500">Harga Beli</div>
            <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::rupiah($record->purchase_price) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
            <div class="text-xs font-bold uppercase text-gray-500">Harga Jual</div>
            <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::rupiah($record->selling_price) }}</div>
        </div>
    </div>

    @if(filled($record->description))
        <div class="rounded-xl border border-gray-200 bg-white p-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
            {{ $record->description }}
        </div>
    @endif
</div>
