<x-filament-widgets::widget class="ig-kpi-section-widget">
    <div class="rounded-2xl border border-amber-100 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-slate-900">
                Persetujuan Operasional
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Pantau dokumen gudang, transfer, dan produksi yang menunggu keputusan.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ($this->getDashboardStats() as $stat)
                <div class="relative rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition duration-150 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                            <p class="mt-2 text-3xl font-semibold leading-none text-slate-950">{{ $stat['value'] }}</p>
                        </div>

                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br {{ $stat['accent'] }} text-white shadow-sm">
                            <x-filament::icon :icon="$stat['icon']" class="h-5 w-5" />
                        </div>
                    </div>

                    <p class="text-sm text-slate-500">{{ $stat['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
