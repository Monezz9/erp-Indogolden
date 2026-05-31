<?php

use App\Http\Controllers\BranchSalePrintController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::middleware('auth')->group(function () {
    Route::get('/branch-sales/{branchSale}/print/thermal', [BranchSalePrintController::class, 'thermal'])
        ->name('branch-sales.print.thermal');

    Route::get('/branch-sales/{branchSale}/print/a4', [BranchSalePrintController::class, 'a4'])
        ->name('branch-sales.print.a4');
});
