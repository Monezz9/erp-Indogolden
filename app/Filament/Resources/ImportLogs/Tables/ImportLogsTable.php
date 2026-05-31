<?php

namespace App\Filament\Resources\ImportLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('module_name')->label('Modul')->searchable(),
                TextColumn::make('file_name')->label('Nama File')->searchable()->toggleable(),
                TextColumn::make('importedBy.name')->label('Diimport Oleh')->toggleable(),
                TextColumn::make('total_rows')->numeric()->label('Total Baris'),
                TextColumn::make('success_rows')->numeric()->label('Berhasil'),
                TextColumn::make('failed_rows')->numeric()->label('Gagal'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Tanggal Import')->dateTime('d M Y H:i')->sortable(),
            ]);
    }
}
