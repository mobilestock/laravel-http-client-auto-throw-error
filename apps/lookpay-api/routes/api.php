<?php

use App\Http\Controllers\EstablishmentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1/invoices')
    ->middleware([Authenticate::class])
    ->group(function () {
        Route::post('/', [InvoiceController::class, 'createInvoice']);
    });

Route::prefix('/establishment')->group(function () {
    Route::post('/invoices', [InvoiceController::class, 'invoicesDetails'])->middleware([Authenticate::class]);
    Route::get('/payment_methods', [EstablishmentController::class, 'getPaymentMethods'])->middleware([
        Authenticate::class,
    ]);
    Route::get('/search_users/{phone_number}', [EstablishmentController::class, 'searchUser']);

    Route::post('/login', [EstablishmentController::class, 'login']);
});
