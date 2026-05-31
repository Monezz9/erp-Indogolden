<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gradient-to-r from-red-700 via-red-600 to-rose-500 p-5 text-white shadow-sm">
            <h2 class="text-2xl font-black">Request Masuk Gudang</h2>
            <p class="mt-1 text-sm text-red-100">Review request, ubah qty disetujui, tambah item bila perlu, lalu setujui, packing, dan kirim ke cabang.</p>
        </div>

        <div class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-3 dark:border-gray-800 dark:bg-gray-900">
            <label class="text-sm">Tanggal Kirim
                <input type="date" wire:model.live="deliveryDate" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
            </label>
            <label class="text-sm">Status
                <select wire:model.live="status" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    @foreach($this->statusOptions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">Cabang
                <select wire:model.live="branchId" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">Semua Cabang</option>
                    @foreach($this->branchOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-[1000px] w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left">Request</th>
                        <th class="px-3 py-2 text-left">Cabang</th>
                        <th class="px-3 py-2 text-left">Tanggal Kirim</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Item</th>
                        <th class="px-3 py-2 text-right">Qty Req</th>
                        <th class="px-3 py-2 text-right">Qty Disetujui</th>
                        <th class="px-3 py-2 text-right">Qty Dipacking</th>
                        <th class="px-3 py-2 text-right">Qty Dikirim</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->rows() as $row)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2">{{ $row->request_number }}</td>
                            <td class="px-3 py-2">{{ $row->branch?->name }}</td>
                            <td class="px-3 py-2">{{ $row->delivery_date?->format('d M Y') }}</td>
                            <td class="px-3 py-2"><span class="rounded-full bg-red-100 px-2 py-1 text-xs text-red-800 dark:bg-red-900/30 dark:text-red-200">{{ \App\Enums\BranchRequestStatus::options()[$row->status] ?? $row->status }}</span></td>
                            <td class="px-3 py-2 text-right">{{ $row->items->count() }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row->items->sum('requested_qty')) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row->items->sum('approved_qty')) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row->items->sum('packed_qty')) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row->items->sum('shipped_qty')) }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ $this->editUrl($row->id) }}" class="rounded-lg bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">Review/Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-3 py-8 text-center text-gray-500">Tidak ada request sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
