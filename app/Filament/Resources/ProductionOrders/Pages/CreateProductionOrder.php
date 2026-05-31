<?php

namespace App\Filament\Resources\ProductionOrders\Pages;

use App\Enums\ProductionOrderStatus;
use App\Filament\Resources\ProductionOrders\ProductionOrderResource;
use App\Models\ProductionRecipe;
use App\Models\User;
use App\Services\ProductionService;
use App\Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateProductionOrder extends CreateRecord
{
    protected static string $resource = ProductionOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            return parent::handleRecordCreation($data);
        }

        $recipe = ProductionRecipe::query()->findOrFail($data['production_recipe_id']);

        $order = app(ProductionService::class)->createOrder(
            recipe: $recipe,
            targetQty: (float) $data['target_qty'],
            creator: $actor,
        );

        $order->update([
            'order_number' => $data['order_number'] ?? $order->order_number,
            'planned_date' => $data['planned_date'] ?? $order->planned_date,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => ProductionOrderStatus::Draft,
        ]);

        return $order->refresh();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ProductionOrderStatus::Draft;
        $data['created_by'] = Auth::id();

        return $data;
    }
}
