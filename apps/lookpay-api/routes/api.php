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
    Route::middleware(Authenticate::class)->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'getInvoicesDetails']);
        Route::get('/payment_methods', [EstablishmentController::class, 'getPaymentMethods']);
    });
    Route::get('/search_users/{phone_number}', [EstablishmentController::class, 'getEstablishmentsByPhoneNumber']);

    Route::post('/login', [EstablishmentController::class, 'login']);
});
