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
        $financialStatementModel = new FinancialStatements();
        $financialStatements = $financialStatementModel->getPendingsFinancialStatements();

        foreach ($financialStatements as $financialStatement) {
            Http::mobilestock()->post('api_pagamento/atualiza_saldo_lookpay', [
                'balance' => $financialStatement['amount'],
                'contributor_id' => $financialStatement['contributor_id'],
            ]);

            $financialStatementModel->where('for', $financialStatement['for'])->update(['is_pending' => true]);
        }
    }
}
