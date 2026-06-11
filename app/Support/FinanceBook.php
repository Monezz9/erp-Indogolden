<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\FinanceExpense;
use App\Models\FinanceIncome;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FinanceBook
{
    public static function rupiah(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return 'Rp'.number_format((float) $value, 0, ',', '.');
    }

    public static function categoryLabel(?string $code, ?string $name, ?string $type = null): string
    {
        $code = Str::upper((string) $code);
        $name = trim((string) $name);

        if (Str::startsWith($code, 'REV')) {
            return 'Pendapatan';
        }

        if (Str::contains($code, ['COGS', 'LOG', 'STOCK', 'SUPPLY'])) {
            return 'Logistik';
        }

        if (Str::contains($code, ['NOPEX', 'NON-OPEX', 'NONOPEX'])) {
            return 'NOPEX';
        }

        if (Str::contains($code, 'OPEX')) {
            return 'OPEX';
        }

        if (Str::contains($code, ['CASH', 'BANK', 'QRIS', 'APP'])) {
            return 'Kas / Bank / Aplikasi';
        }

        if ($name !== '') {
            if (Str::contains(Str::lower($name), ['sales', 'revenue', 'pendapatan'])) {
                return 'Pendapatan';
            }

            if (Str::contains(Str::lower($name), ['cogs', 'logistik', 'bahan', 'supplier'])) {
                return 'Logistik';
            }

            if (Str::contains(Str::lower($name), ['operational', 'operasional', 'opex'])) {
                return 'OPEX';
            }

            return $name;
        }

        return $type === 'income' ? 'Pendapatan' : 'Lain-lain';
    }

    public static function paymentLabel(mixed $paymentMethod): string
    {
        $value = $paymentMethod instanceof PaymentMethod ? $paymentMethod->value : (string) $paymentMethod;

        return PaymentMethod::options()[$value] ?? Str::headline(str_replace('_', ' ', $value));
    }

    public static function description(Model $record): string
    {
        $notes = trim((string) ($record->notes ?? ''));

        if ($notes !== '') {
            return $notes;
        }

        $parts = array_filter([
            $record->transaction_number ?? null,
            $record->branch?->name ?? null,
            self::paymentLabel($record->payment_method ?? ''),
        ]);

        return implode(' - ', $parts) ?: '-';
    }

    public static function runningIncomeBalance(FinanceIncome $record): float
    {
        return (float) static::scopeForUser(FinanceIncome::query())
            ->where(function (Builder $query) use ($record): void {
                $query
                    ->where('transaction_date', '<', $record->transaction_date)
                    ->orWhere(function (Builder $query) use ($record): void {
                        $query
                            ->where('transaction_date', '=', $record->transaction_date)
                            ->where('id', '<=', $record->id);
                    });
            })
            ->sum('amount');
    }

    public static function runningExpenseBalance(FinanceExpense $record): float
    {
        return -1 * (float) static::scopeForUser(FinanceExpense::query())
            ->where(function (Builder $query) use ($record): void {
                $query
                    ->where('transaction_date', '<', $record->transaction_date)
                    ->orWhere(function (Builder $query) use ($record): void {
                        $query
                            ->where('transaction_date', '=', $record->transaction_date)
                            ->where('id', '<=', $record->id);
                    });
            })
            ->sum('amount');
    }

    /**
     * @return array{income: float, expense: float, balance: float, cash: float, bank: float, application: float}
     */
    public static function summary(): array
    {
        $incomeQuery = static::scopeForUser(FinanceIncome::query());
        $expenseQuery = static::scopeForUser(FinanceExpense::query());

        $income = (float) (clone $incomeQuery)->sum('amount');
        $expense = (float) (clone $expenseQuery)->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'cash' => static::paymentBalance('cash'),
            'bank' => static::paymentBalance('bank_transfer') + static::paymentBalance('debit'),
            'application' => static::paymentBalance('qris'),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function cashFlowRows(?string $search = null, ?int $branchId = null, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $incomeRows = static::scopeForUser(FinanceIncome::query())
            ->with(['branch', 'category'])
            ->when($branchId, fn (Builder $query): Builder => $query->where('branch_id', $branchId))
            ->when($startDate, fn (Builder $query): Builder => $query->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn (Builder $query): Builder => $query->whereDate('transaction_date', '<=', $endDate))
            ->get()
            ->map(fn (FinanceIncome $record): array => static::row($record, 'income'));

        $expenseRows = static::scopeForUser(FinanceExpense::query())
            ->with(['branch', 'category', 'supplier'])
            ->when($branchId, fn (Builder $query): Builder => $query->where('branch_id', $branchId))
            ->when($startDate, fn (Builder $query): Builder => $query->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn (Builder $query): Builder => $query->whereDate('transaction_date', '<=', $endDate))
            ->get()
            ->map(fn (FinanceExpense $record): array => static::row($record, 'expense'));

        $balance = 0.0;

        return $incomeRows
            ->merge($expenseRows)
            ->sortBy([
                ['transaction_date', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values()
            ->map(function (array $row) use (&$balance): array {
                $balance += (float) $row['debit'] - (float) $row['credit'];
                $row['balance'] = $balance;

                return $row;
            })
            ->when($search, function (Collection $rows) use ($search): Collection {
                $needle = Str::lower((string) $search);

                return $rows
                    ->filter(fn (array $row): bool => Str::contains(Str::lower(implode(' ', [
                        $row['category'],
                        $row['description'],
                        $row['branch'],
                        $row['payment_method'],
                        $row['transaction_number'],
                    ])), $needle))
                    ->values();
            });
    }

    public static function scopeForUser(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $query;
        }

        if ($user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::Finance->value,
            UserRole::HeadLogistics->value,
        ])) {
            return $query;
        }

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    protected static function paymentBalance(string $paymentMethod): float
    {
        $income = (float) static::scopeForUser(FinanceIncome::query())
            ->where('payment_method', $paymentMethod)
            ->sum('amount');

        $expense = (float) static::scopeForUser(FinanceExpense::query())
            ->where('payment_method', $paymentMethod)
            ->sum('amount');

        return $income - $expense;
    }

    protected static function row(Model $record, string $type): array
    {
        $amount = (float) $record->amount;

        return [
            'key' => $type.'-'.$record->id,
            'sort_id' => $record->id,
            'transaction_number' => $record->transaction_number,
            'transaction_date' => $record->transaction_date,
            'date_label' => $record->transaction_date?->format('d M Y') ?? '-',
            'category' => static::categoryLabel($record->category?->code, $record->category?->name, $record->category?->type),
            'description' => static::description($record),
            'branch' => $record->branch?->name ?? '-',
            'payment_method' => static::paymentLabel($record->payment_method),
            'debit' => $type === 'income' ? $amount : 0,
            'credit' => $type === 'expense' ? $amount : 0,
            'balance' => 0,
            'type' => $type,
        ];
    }
}
