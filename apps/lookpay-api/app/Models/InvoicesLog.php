<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Support\Carbon;

/**
 * App\Models\InvoicesLog
 *
 * @property string $id
 * @property string $description
 * @property string $payload
 * @property Carbon $created_at
 */
class InvoicesLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['id', 'description', 'payload'];
    protected $casts = ['payload' => AsCollection::class . ':' . Invoice::class];
}
