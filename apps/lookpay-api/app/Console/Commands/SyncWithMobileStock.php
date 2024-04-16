<?php

namespace App\Console\Commands;

use App\Models\FinancialStatements;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncWithMobileStock extends Command
{
    protected $signature = 'app:sync-with-mobile-stock';

    public function handle()
    {
        $establishments = FinancialStatements::getEstablishmentsNotSynced();

        foreach ($establishments as $establishment) {
            Http::mobilestock()->post('api_pagamento/atualiza_saldo_lookpay', [
                'valor' => $establishment['amount'],
                'id_colaborador' => $establishment['contributor_id'],
            ]);

            FinancialStatements::markAsSynced($establishment['ids']);
        }
    }
}
