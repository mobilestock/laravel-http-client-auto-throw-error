<?php

namespace App\Models;

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
    protected $fillable = [
        'id',
        'for',
        'amount',
        'type'
    ];

    protected static function boot(): void
    {
        parent::boot();
    }
}
