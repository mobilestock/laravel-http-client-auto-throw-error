<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Support\Carbon;

/**
 * App\Models\InvoiceLog
 *
 * @property string $id
 * @property string $description
 * @property string $payload
 * @property ?Carbon $created_at
 */
class InvoiceLog extends Model
{
    public $timestamps = false;
    public $table = 'invoices_logs';
    protected $fillable = ['id', 'description', 'payload'];
    protected $casts = ['payload' => AsCollection::class . ':' . Invoice::class];
    protected static function boot(): void
    {
        parent::boot();

        self::updating(fn() => self::throwBadMethodCallException('Invoice log is immutable'));
    }
}
