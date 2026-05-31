<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gradient-to-r from-red-700 via-red-600 to-rose-500 p-5 text-white shadow-sm">
            <h2 class="text-2xl font-black">Import Data</h2>
            <p class="mt-1 text-sm text-red-100">Import untuk master, transaksi, workflow, request cabang, pengiriman, dan HPP.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-3 md:grid-cols-3">
                <label class="text-sm">Modul
                    <select wire:model="module" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        @foreach($this->moduleMap() as $key => $modelClass)
                            <option value="{{ $key }}">{{ str($key)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm">Mode Import
                    <select wire:model="mode" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        <option value="insert_only">Tambah data baru saja</option>
                        <option value="update_existing">Update data yang sudah ada</option>
                        <option value="upsert">Upsert</option>
                        <option value="replace">Ganti data</option>
                    </select>
                </label>

                <label class="text-sm">Upload File (.xlsx/.csv)
                    <input type="file" wire:model="file" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white text-sm dark:border-gray-700 dark:bg-gray-800" />
                </label>
            </div>

            <div class="mt-4 flex gap-2">
                <x-filament::button color="gray" wire:click="preview">Preview Data</x-filament::button>
                <x-filament::button color="danger" wire:click="import">Konfirmasi Import</x-filament::button>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="font-bold">Preview (maksimal 15 baris)</h3>
            <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-sm">
                    <tbody>
                        @forelse($previewRows as $index => $row)
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2 align-top text-xs text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-3 py-2">
                                    <pre class="whitespace-pre-wrap text-xs">{{ json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3 py-8 text-center text-sm text-gray-500">Belum ada preview. Upload file lalu klik Preview Data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
