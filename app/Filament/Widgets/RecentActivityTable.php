<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentActivityTable extends TableWidget
{
    protected static ?string $heading = 'Jejak Aktivitas Terbaru';

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::HeadLogistics->value,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ActivityLog::query()->latest('logged_at')->limit(10))
            ->columns([
                TextColumn::make('logged_at')->label('Waktu')->dateTime('d M H:i'),
                TextColumn::make('user.name')->label('User')->toggleable(),
                TextColumn::make('module')->badge(),
                TextColumn::make('action')->badge(),
                TextColumn::make('description')->label('Keterangan')->limit(48),
            ]);
    }
}
