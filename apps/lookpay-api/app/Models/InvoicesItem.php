<?php

namespace App\Models;

use App\Enum\Invoice\InvoiceItemTypeEnum;
use Illuminate\Support\Carbon;

/**
 * App\Models\InvoicesItem
 *
 * @property string $id
 * @property string $invoice_id
 * @property InvoiceItemTypeEnum $type
 * @property int $amount
 * @property Carbon $created_at
 */
class InvoicesItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['id', 'invoice_id', 'type', 'amount'];
    protected $casts = ['type' => InvoiceItemTypeEnum::class];
}
