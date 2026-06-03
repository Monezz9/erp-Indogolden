<?php

namespace App\Filament\Resources\ReceiptSettings;

use App\Filament\Resources\ReceiptSettings\Pages\CreateReceiptSetting;
use App\Filament\Resources\ReceiptSettings\Pages\EditReceiptSetting;
use App\Filament\Resources\ReceiptSettings\Pages\ListReceiptSettings;
use App\Filament\Resources\ReceiptSettings\Schemas\ReceiptSettingForm;
use App\Filament\Resources\ReceiptSettings\Tables\ReceiptSettingsTable;
use App\Models\ReceiptSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReceiptSettingResource extends Resource
{
    protected static ?string $model = ReceiptSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected static ?string $navigationLabel = 'Template Nota';

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?string $modelLabel = 'Template Nota';

    protected static ?string $pluralModelLabel = 'Template Nota';

    public static function form(Schema $schema): Schema
    {
        return ReceiptSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceiptSettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReceiptSettings::route('/'),
            'create' => CreateReceiptSetting::route('/create'),
            'edit' => EditReceiptSetting::route('/{record}/edit'),
        ];
    }
}
