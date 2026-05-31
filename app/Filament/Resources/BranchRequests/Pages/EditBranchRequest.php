<?php

namespace App\Filament\Resources\BranchRequests\Pages;

use App\Enums\BranchRequestStatus;
use App\Filament\Resources\BranchRequests\BranchRequestResource;
use App\Models\User;
use Filament\Actions\Action;
use App\Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class EditBranchRequest extends EditRecord
{
    protected static string $resource = BranchRequestResource::class;

    public static string|Alignment $formActionsAlignment = Alignment::End;

    public static bool $formActionsAreSticky = true;

    public function getTitle(): string
    {
        return 'Detail Request Barang';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Perubahan')
            ->visible(fn (): bool => ! $this->isBranchRecordLocked());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->isBranchRecordLocked()) {
            throw new AuthorizationException('Cabang hanya bisa mengubah request saat status masih draft.');
        }

        return $data;
    }

    protected function isBranchRecordLocked(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->isBranchLike()
            && $this->getRecord()->status !== BranchRequestStatus::Draft->value;
    }
}
