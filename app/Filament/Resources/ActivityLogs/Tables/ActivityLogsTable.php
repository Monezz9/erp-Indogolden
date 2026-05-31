<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('logged_at')->label('Waktu')->dateTime('d M Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Pengguna')->searchable(),
                TextColumn::make('module')->label('Modul')->badge(),
                TextColumn::make('action')->label('Aksi')->badge(),
                TextColumn::make('description')->label('Keterangan')->limit(50)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->label('Modul')
                    ->options(fn () => ActivityLog::query()->distinct()->pluck('module', 'module')->all()),
            ]);
    }
}
