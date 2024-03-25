<?php

namespace App\Jobs;

use App\Enum\WebhookEventTypeEnum;
use App\Models\EstablishmentWebhook;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InvoiceWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    protected string $establishmentId;
    protected Invoice $invoice;
    protected WebhookEventTypeEnum $eventType;
    public $tries = 5;
    public function __construct(string $establishmentId, Invoice $invoice, WebhookEventTypeEnum $eventType)
    {
        $this->establishmentId = $establishmentId;
        $this->eventType = $eventType;
        $this->invoice = $invoice;
    }

    public function handle(): void
    {
        $indexOpensearch = env('OPENSEARCH_INDEX');
        $opensearch = Http::baseUrl(env('OPENSEARCH_ENDPOINT'));
        $webhook = EstablishmentWebhook::where('event_type', $this->eventType)
            ->where('establishment_id', $this->establishmentId)
            ->firstOrFail();

        $opensearch->post("/$indexOpensearch/_doc", [
            'invoice' => $this->invoice,
            'event_type' => $this->eventType,
            'response' => null,
            'created_at' => (new Carbon('NOW'))->toDateTimeString(),
        ]);

        $response = Http::post($webhook->url, [
            'invoice' => $this->invoice,
            'event_type' => $this->eventType,
        ]);

        $opensearch->post("/$indexOpensearch/_doc", [
            'invoice' => $this->invoice,
            'event_type' => $this->eventType,
            'response' => $response->json(),
            'created_at' => (new Carbon('NOW'))->toDateTimeString(),
        ]);

        if ($response->status() !== 200) {
            throw new RuntimeException('Webhook failed' . $response->body() . ' ' . $response->status());
        }
    }
}
