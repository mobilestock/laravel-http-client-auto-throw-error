<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\FinancialStatements
 *
 * @property string $id
 * @property string $for
 * @property float $amount
 * @property string $type
 * @property Carbon $created_at
 */
class FinancialStatements extends Model
{
    public $timestamps = false;
    protected $fillable = ['id', 'for', 'amount', 'type'];

    public static function getEstablishmentsNotSynced(): array
    {
        $financialStatements = DB::select(
            "SELECT
                GROUP_CONCAT(financial_statements.id) json_ids,
                SUM(financial_statements.amount) amount,
                mobilestock_users.contributor_id
            FROM financial_statements
            INNER JOIN mobilestock_users ON mobilestock_users.id = financial_statements.for
            WHERE NOT financial_statements.is_synced
            GROUP BY financial_statements.for"
        );

        return $financialStatements;
    }

    public static function markAsSynced(array $establishmentsIds): void
    {
        // https://github.com/mobilestock/backend/issues/36
        self::whereIn('financial_statements.for', $establishmentsIds)->update('financial_statements.is_synced', true);
    }
}
