<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

/**
 * App\Models\FinancialStatements
 *
 * @property string $id
 * @property string $for
 * @property float $amount
 * @property string $type
 * @property \Carbon\Carbon|null $created_at
 */
class FinancialStatements extends Model
{
    public $timestamps = false;
    protected $fillable = ['id', 'for', 'amount', 'type'];

    protected static function boot(): void
    {
        parent::boot();
    }

    public function getPendingsFinancialStatements()
    {
        $financialStatements = DB::select(
            "SELECT
                financial_statements.id,
                SUM(financial_statements.amount) amount,
                financial_statements.for,
                mobilestock_users.contributor_id
            FROM financial_statements
            INNER JOIN mobilestock_users ON mobilestock_users.id = financial_statements.for
            WHERE financial_statements.is_pending = false
            GROUP BY financial_statements.for"
        );

        return $financialStatements;
    }
}
