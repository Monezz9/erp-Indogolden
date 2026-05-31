<?php

namespace App\Http\Controllers;

use App\Models\BranchSale;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BranchSalePrintController extends Controller
{
    public function thermal(BranchSale $branchSale): Response
    {
        Gate::authorize('view', $branchSale);

        $branchSale->loadMissing('items.item.defaultUnit', 'branch', 'creator', 'poster');

        return response()->view('branch-sales.print-thermal', [
            'sale' => $branchSale,
        ]);
    }

    public function a4(BranchSale $branchSale): Response
    {
        Gate::authorize('view', $branchSale);

        $branchSale->loadMissing('items.item.defaultUnit', 'branch', 'creator', 'poster');

        return response()->view('branch-sales.print-a4', [
            'sale' => $branchSale,
        ]);
    }
}
