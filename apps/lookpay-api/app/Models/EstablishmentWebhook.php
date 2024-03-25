<?php

namespace App\Models;
use App\Enum\WebhookEventTypeEnum;

/**
 *  App\Models\EstablishmentWebhook
 *
 * @property string $id
 * @property string $establishment_id
 * @property string $url
 * @property ?string $secret_token
 * @property WebhookEventTypeEnum $event_type
 */
class EstablishmentWebhook extends Model
{
    protected $table = 'establishments_webhooks';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'establishment_id', 'url', 'secret_token', 'event_type'];
    protected $casts = [
        'event_type' => WebhookEventTypeEnum::class,
    ];
}
