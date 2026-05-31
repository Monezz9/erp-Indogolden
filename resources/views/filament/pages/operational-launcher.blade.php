<x-filament-panels::page>
    @php
        $menus = $this->menus();
    @endphp

    <style>
        [x-cloak] { display: none !important; }
        .ol-shell { --ol-rgb: 185, 28, 28; }
        .ol-accent-rose { --ol-rgb: 225, 29, 72; }
        .ol-accent-amber { --ol-rgb: 217, 119, 6; }
        .ol-accent-red { --ol-rgb: 185, 28, 28; }
        .ol-accent-slate { --ol-rgb: 71, 85, 105; }
        .ol-accent-emerald { --ol-rgb: 5, 150, 105; }
        .ol-accent-sky { --ol-rgb: 2, 132, 199; }
        .ol-accent-violet { --ol-rgb: 124, 58, 237; }
        .ol-hero {
            background:
                linear-gradient(120deg, rgba(127, 29, 29, 0.95), rgba(185, 28, 28, 0.88)),
                radial-gradient(circle at 82% 18%, rgba(255, 255, 255, 0.22), transparent 34%);
        }
        .ol-tab-active {
            background: rgb(var(--ol-rgb));
            border-color: rgb(var(--ol-rgb));
            color: #fff;
            box-shadow: 0 6px 14px rgba(var(--ol-rgb), 0.14);
        }
        .ol-card {
            border-color: rgba(var(--ol-rgb), 0.18);
        }
        .ol-card:hover {
            border-color: rgba(var(--ol-rgb), 0.42);
            box-shadow: 0 8px 18px rgba(var(--ol-rgb), 0.08);
            transform: translateY(-1px);
        }
        .ol-icon {
            background: rgba(var(--ol-rgb), 0.11);
            color: rgb(var(--ol-rgb));
        }
        .ol-action {
            background: rgb(var(--ol-rgb));
        }
        .ol-shell svg {
            display: block;
            flex-shrink: 0;
            max-width: 2.5rem;
            max-height: 2.5rem;
        }
        .ol-tab-icon svg {
            width: 1rem;
            height: 1rem;
        }
        .ol-shortcut-icon svg {
            width: 1.25rem;
            height: 1.25rem;
        }
    </style>

    <div
        x-data="{ active: 'master-data' }"
        class="ol-shell space-y-6"
    >
        <section class="ol-hero overflow-hidden rounded-xl p-5 text-white shadow-sm md:p-6">
            <div class="grid gap-5 lg:grid-cols-[1.45fr_0.55fr] lg:items-end">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-red-100">Prototype launcher</p>
                    <h1 class="mt-2 text-2xl font-black tracking-normal md:text-3xl">Menu Operasional ERP</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-red-50">
                        Pintu masuk cepat untuk operator gudang, finance, produksi, cabang, dan admin tanpa mengubah sidebar atau modul lama.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-2 rounded-lg border border-white/20 bg-white/10 p-2.5 text-center backdrop-blur">
                    <div>
                        <div class="text-lg font-black">7</div>
                        <div class="text-[11px] text-red-100">Kategori</div>
                    </div>
                    <div>
                        <div class="text-lg font-black">30+</div>
                        <div class="text-[11px] text-red-100">Shortcut</div>
                    </div>
                    <div>
                        <div class="text-lg font-black">0</div>
                        <div class="text-[11px] text-red-100">Logic Baru</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-2 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex gap-2 overflow-x-auto">
                @foreach($menus as $key => $menu)
                    <button
                        type="button"
                        x-on:click="active = '{{ $key }}'"
                        class="ol-accent-{{ $menu['accent'] }} inline-flex min-w-max items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition duration-200 hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                        :class="active === '{{ $key }}' ? 'ol-tab-active' : ''"
                    >
                        <span class="ol-tab-icon">
                            <x-dynamic-component :component="$menu['icon']" />
                        </span>
                        <span>{{ $menu['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        @foreach($menus as $key => $menu)
            <section
                x-cloak
                x-show="active === '{{ $key }}'"
                x-transition.opacity.duration.150ms
                class="ol-accent-{{ $menu['accent'] }} space-y-5"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-md border border-gray-200 bg-white px-2 py-1 text-[11px] font-semibold text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                            {{ $menu['label'] }}
                        </div>
                        <h2 class="mt-2 text-xl font-black text-gray-950 dark:text-white">{{ $menu['label'] }}</h2>
                        <p class="mt-1 max-w-3xl text-sm leading-5 text-gray-600 dark:text-gray-300">{{ $menu['subtitle'] }}</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach($menu['items'] as $item)
                        <a
                            href="{{ $item['url'] }}"
                            class="ol-card group flex min-h-[150px] flex-col rounded-xl border bg-white p-4 shadow-sm transition duration-200 dark:bg-gray-900"
                        >
                            <div class="flex items-start gap-3">
                                <div class="ol-icon ol-shortcut-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-lg">
                                    <x-dynamic-component :component="$item['icon']" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-bold leading-5 text-gray-950 dark:text-white">{{ $item['title'] }}</h3>
                                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-600 dark:text-gray-300">{{ $item['description'] }}</p>
                                </div>
                            </div>

                            <div class="mt-auto pt-4">
                                <span class="ol-action inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold text-white transition group-hover:brightness-95">
                                    {{ $item['action'] }}
                                    <span class="ml-1 text-xs leading-none">›</span>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
