@php
    $steps = [
        'draft' => 'Draft',
        'submitted' => 'Submit',
        'reviewed' => 'Review',
        'approved' => 'Approve',
        'shipped' => 'Kirim',
        'received' => 'Terima',
    ];

    $status = $record->status instanceof \App\Enums\BranchRequestStatus
        ? $record->status->value
        : (string) $record->status;

    $rank = [
        'draft' => 0,
        'submitted' => 1,
        'reviewed' => 2,
        'approved' => 3,
        'packed' => 3,
        'shipped' => 4,
        'received' => 5,
        'rejected' => -1,
    ][$status] ?? 0;
@endphp

<div class="min-w-[300px] py-1">
    <div class="flex items-center gap-1.5">
        @foreach ($steps as $value => $label)
            @php
                $index = $loop->index;
                $isDone = $rank >= $index;
                $isRejected = $status === 'rejected';
            @endphp

            <div class="flex min-w-0 flex-1 items-center gap-1.5">
                <div @class([
                    'flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-[10px] font-black',
                    'border-red-200 bg-red-50 text-red-700' => $isRejected,
                    'border-emerald-200 bg-emerald-50 text-emerald-700' => ! $isRejected && $isDone,
                    'border-slate-200 bg-white text-slate-400 dark:border-slate-700 dark:bg-slate-900' => ! $isRejected && ! $isDone,
                ])>
                    {{ $index + 1 }}
                </div>

                @if (! $loop->last)
                    <div @class([
                        'h-0.5 min-w-3 flex-1 rounded-full',
                        'bg-red-200' => $isRejected,
                        'bg-emerald-300' => ! $isRejected && $rank > $index,
                        'bg-slate-200 dark:bg-slate-700' => ! $isRejected && $rank <= $index,
                    ])></div>
                @endif
            </div>
        @endforeach
    </div>
    <div class="mt-1 grid grid-cols-6 gap-1 text-[10px] font-semibold text-slate-500">
        @foreach ($steps as $label)
            <span class="truncate">{{ $label }}</span>
        @endforeach
    </div>
</div>
