<?php

namespace App\Support\Excel;

use App\Enums\UserRole;
use App\Exports\StyledArrayExport;
use App\Imports\HeadingRowsImport;
use App\Models\BranchSale;
use App\Models\FinanceExpense;
use App\Models\FinanceIncome;
use App\Models\ImportLog;
use App\Models\StockMovement;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceExcelManager
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function exportQuery(string $modelClass, Builder $query, string $filenamePrefix): BinaryFileResponse
    {
        if (! app()->bound('excel')) {
            throw new \RuntimeException('Paket Excel belum aktif. Jalankan composer dump-autoload lalu refresh browser.');
        }

        $columns = $this->getExportableColumns($modelClass);
        $rows = $query->get()->map(fn (Model $record): array => Arr::only($record->toArray(), $columns));

        $filename = str($filenamePrefix)->snake()->append('_'.now()->format('Ymd_His').'.xlsx')->toString();

        return app('excel')->download(new StyledArrayExport($rows, $columns), $filename);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function exportTemplate(string $modelClass): BinaryFileResponse
    {
        $columns = $this->getExportableColumns($modelClass);
        $filename = str(class_basename($modelClass))->snake()->append('_template.xlsx')->toString();

        return app('excel')->download(new StyledArrayExport(collect(), $columns), $filename);
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array{created:int,updated:int,skipped:int}
     */
    public function importFromStoredFile(string $modelClass, string $storedPath, ?User $actor = null, string $mode = 'upsert'): array
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException('Model class tidak valid untuk import.');
        }

        if ($actor?->isBranchLike() && ! in_array($modelClass, [BranchSale::class, StockMovement::class, Transfer::class], true)) {
            throw new \InvalidArgumentException('Role cabang hanya boleh import data transaksi cabang.');
        }

        $disk = config('filament.default_filesystem_disk', 'local');
        $filePath = Storage::disk($disk)->path($storedPath);

        $reader = new HeadingRowsImport();

        if (! app()->bound('excel')) {
            throw new \RuntimeException('Paket Excel belum aktif. Jalankan composer dump-autoload lalu refresh browser.');
        }

        app('excel')->import($reader, $filePath);

        $rows = $reader->rows;

        if ($rows->isEmpty()) {
            $this->storeImportLog(
                moduleName: class_basename($modelClass),
                fileName: basename($storedPath),
                actor: $actor,
                totalRows: 0,
                successRows: 0,
                failedRows: 0,
                status: 'success',
            );

            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $fillable = $this->getImportableColumns($modelClass);
        $identifierPriority = $this->identifierPriority($modelClass);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        if ($mode === 'replace') {
            $modelClass::query()->delete();
        }

        DB::transaction(function () use ($rows, $fillable, $identifierPriority, $modelClass, $actor, $mode, &$created, &$updated, &$skipped): void {
            foreach ($rows as $row) {
                $source = $this->normalizeRow($row instanceof Collection ? $row->all() : (array) $row);

                $payload = Arr::only($source, array_merge($fillable, ['id']));

                if ($actor?->isBranchLike() && in_array('branch_id', $fillable, true)) {
                    $payload['branch_id'] = $actor->branch_id;
                }

                if ($actor && in_array('created_by', $fillable, true) && blank($payload['created_by'] ?? null)) {
                    $payload['created_by'] = $actor->id;
                }

                if ($actor && in_array('requested_by', $fillable, true) && blank($payload['requested_by'] ?? null)) {
                    $payload['requested_by'] = $actor->id;
                }

                if ($payload === []) {
                    $skipped++;

                    continue;
                }

                $this->validatePayload($modelClass, $payload);

                $identifier = $this->resolveIdentifier($payload, $identifierPriority);

                if ($identifier === []) {
                    if ($mode === 'update_existing') {
                        $skipped++;

                        continue;
                    }

                    $record = new $modelClass();
                    $record->fill(Arr::except($payload, ['id']));
                    $record->save();
                    $created++;

                    continue;
                }

                $record = $modelClass::query()->where($identifier)->first();

                if ($record) {
                    if ($mode === 'insert_only') {
                        $skipped++;

                        continue;
                    }

                    $record->fill(Arr::except($payload, ['id']));
                    $record->save();
                    $updated++;

                    continue;
                }

                if ($mode === 'update_existing') {
                    $skipped++;

                    continue;
                }

                $record = new $modelClass();
                $record->fill(Arr::except($payload, ['id']));
                $record->save();
                $created++;
            }
        });

        $result = [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];

        $this->storeImportLog(
            moduleName: class_basename($modelClass),
            fileName: basename($storedPath),
            actor: $actor,
            totalRows: $rows->count(),
            successRows: $created + $updated,
            failedRows: $skipped,
            status: $skipped > 0 ? 'partial' : 'success',
            metadata: $result,
        );

        return $result;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, string>
     */
    public function getExportableColumns(string $modelClass): array
    {
        $model = new $modelClass();
        $fillable = $model->getFillable();

        return array_values(array_filter(array_merge(['id'], $fillable), static fn (string $column): bool => ! in_array($column, ['password', 'remember_token'], true)));
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, string>
     */
    protected function getImportableColumns(string $modelClass): array
    {
        $model = new $modelClass();

        return array_values(array_filter($model->getFillable(), static fn (string $column): bool => ! in_array($column, ['password', 'remember_token'], true)));
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, string>
     */
    protected function identifierPriority(string $modelClass): array
    {
        return match ($modelClass) {
            BranchSale::class => ['sale_number', 'id'],
            StockMovement::class => ['movement_number', 'id'],
            Transfer::class => ['transfer_number', 'id'],
            default => ['id', 'code', 'sku', 'email', 'transaction_number', 'name'],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $priority
     * @return array<string, mixed>
     */
    protected function resolveIdentifier(array $payload, array $priority): array
    {
        foreach ($priority as $key) {
            if (! array_key_exists($key, $payload) || blank($payload[$key])) {
                continue;
            }

            return [$key => $payload[$key]];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalizedKey = (string) str((string) $key)
                ->lower()
                ->replace([' ', '-'], '_');

            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $payload
     */
    protected function validatePayload(string $modelClass, array $payload): void
    {
        if (! in_array($modelClass, [FinanceIncome::class, FinanceExpense::class], true)) {
            return;
        }

        if (! array_key_exists('amount', $payload) || (float) $payload['amount'] <= 0) {
            throw new \InvalidArgumentException('Nominal finance harus lebih besar dari 0.');
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function storeImportLog(
        string $moduleName,
        string $fileName,
        ?User $actor,
        int $totalRows,
        int $successRows,
        int $failedRows,
        string $status,
        ?string $errorFileUrl = null,
        array $metadata = [],
    ): void {
        if (! class_exists(ImportLog::class)) {
            return;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('import_logs')) {
            return;
        }

        ImportLog::query()->create([
            'module_name' => $moduleName,
            'file_name' => $fileName,
            'imported_by' => $actor?->id,
            'total_rows' => $totalRows,
            'success_rows' => $successRows,
            'failed_rows' => $failedRows,
            'status' => $status,
            'error_file_url' => $errorFileUrl,
            'metadata' => $metadata,
        ]);
    }
}
