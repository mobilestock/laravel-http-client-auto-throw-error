<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BalanceUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:balance-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pass the values of the establishments to its mobile stock account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $financialStatements = DB::select(
            "SELECT
                financial_statements.id,
                SUM(financial_statements.amount) amount,
                financial_statements.for
            FROM financial_statements
            WHERE financial_statements.created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
            GROUP BY financial_statements.for"
        );

        foreach ($financialStatements as $financialStatement) {
            $mobileStockId = DB::selectOne(
                "SELECT mobilestock_users.contributor_id
                FROM mobilestock_users
                WHERE mobilestock_users.id = :lookpay_id",
                ['lookpay_id' => $financialStatement['for']]
            );

            Http::mobilestock()->post('api_pagamento/balance_update/credit', [
                "balance" => $financialStatement['amount'],
                "contributor_id" => $mobileStockId['contributor_id']
            ]);
        }
    }
}
