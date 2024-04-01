<?php

namespace App\Models;

use App\Enum\Invoice\ItemTypeEnum;
use Illuminate\Support\Carbon;

/**
 * App\Models\InvoiceItem
 *
 * @property string $id
 * @property string $invoice_id
 * @property ItemTypeEnum $type
 * @property float $amount
 * @property ?Carbon $created_at
 */
class InvoiceItem extends Model
{
    protected $table = 'invoices_items';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'invoice_id', 'type', 'amount'];
    protected $casts = ['type' => ItemTypeEnum::class];
}
