<?php

namespace App\Http\Controllers;

use App\Models\BranchSale;
use App\Models\ReceiptSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BranchSalePrintController extends Controller
{
    public function thermal(BranchSale $branchSale): Response
    {
        Gate::authorize('view', $branchSale);

        $branchSale->loadMissing('items.item.defaultUnit', 'branch', 'cashier', 'creator', 'poster');

        return response()->view('branch-sales.print-thermal', [
            'sale' => $branchSale,
        ]);
    }

    public function a4(BranchSale $branchSale): Response
    {
        Gate::authorize('view', $branchSale);

        $branchSale->loadMissing('items.item.defaultUnit', 'branch', 'cashier', 'creator', 'poster');

        return response()->view('branch-sales.print-a4', [
            'sale' => $branchSale,
        ]);
    }

    public function receipt(BranchSale $branchSale): Response
    {
        Gate::authorize('view', $branchSale);

        $branchSale->loadMissing('items.item.defaultUnit', 'branch', 'cashier', 'creator', 'poster');

        return response()->view('pos.receipt-print', [
            'sale' => $branchSale,
            'setting' => ReceiptSetting::current(),
        ]);
    }
}
