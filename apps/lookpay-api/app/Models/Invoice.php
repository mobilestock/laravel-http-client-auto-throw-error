<?php

namespace App\Models;

use App\Enum\Invoice\ItemTypeEnum;
use App\Enum\Invoice\PaymentMethodsEnum;
use App\Enum\Invoice\StatusEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * App\Models\Invoice
 *
 * @property string $id
 * @property string $establishment_id
 * @property PaymentMethodsEnum $payment_method
 * @property float $installments
 * @property float $amount
 * @property float $fee
 * @property ?string $external_id
 * @property ?string $reference_id
 * @property StatusEnum $status
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class Invoice extends Model
{
    public $timestamps = false;
    protected $comissions;
    protected $appends = ['comissions'];
    protected $fillable = [
        'id',
        'establishment_id',
        'payment_method',
        'amount',
        'fee',
        'external_id',
        'reference_id',
        'status',
    ];
    protected $casts = ['payment_method' => PaymentMethodsEnum::class, 'status' => StatusEnum::class];
    protected static function boot(): void
    {
        parent::boot();

        self::created([self::class, 'invoiceAfterInsert']);
        self::updated([self::class, 'invoiceAfterUpdate']);
    }
    public static function getCompleteInvoiceById(string $invoiceId): self
    {
        $invoice = self::where('id', $invoiceId)->firstOrFail();
        $invoice->comissions = $invoice
            ->hasMany(InvoiceItem::class, 'invoice_id', 'id')
            ->orderBy('created_at', 'DESC')
            ->get();

        return $invoice;
    }
    /**
     * @return Collection<InvoiceItem>|null
     */
    public function getComissionsAttribute()
    {
        $comissions = null;
        if (InvoiceItem::where('invoice_id', $this->id)->exists()) {
            $comissions = $this->hasMany(InvoiceItem::class, 'invoice_id', 'id')
                ->orderBy('created_at', 'DESC')
                ->get();
        }

        return $comissions;
    }

    public static function invoiceAfterInsert(self $model): void
    {
        $model->refresh();
        $log = new InvoiceLog();
        $log->description = 'INVOICE CREATED';
        $log->payload = $model;
        $log->save();
    }
    public static function invoiceAfterUpdate(self $model): void
    {
        $log = new InvoiceLog();
        switch ($model->status) {
            case $model->getOriginal('status'):
                $log->description = 'INVOICE EDITED';
                break;
            case StatusEnum::PENDING:
                $log->description = 'INVOICE PENDING';
                break;
            case StatusEnum::PAID:
                $log->description = 'INVOICE PAID';
                break;
            case StatusEnum::REFUNDED:
                $log->description = 'INVOICE CANCELED';
                break;
            case StatusEnum::EXPIRED:
                $log->description = 'INVOICE EXPIRED';
                break;
        }
        $log->payload = $model;
        $log->save();
    }

    public static function getInvoicesDetails(array $request, string $establishmentId): array
    {
        $page = $request['page'] ?? 1;
        $itensPerPage = 50;
        $offset = ($page - 1) * $itensPerPage;
        $bind = [];

        $dates = [
            'initial_date' => $request['initial_date'] ?? null,
            'final_date' => $request['final_date'] ?? null,
        ];
        $paymentMethod = $request['payment_method'] ?? null;
        $search = $request['search'] ?? null;

        $date = '';
        if ($dates['initial_date'] && !$dates['final_date']) {
            $date = 'AND invoices.created_at >= :initial_date';
            $bind = ['initial_date' => $dates['initial_date']];
        } elseif ($dates['final_date'] && !$dates['initial_date']) {
            $date = 'AND invoices.created_at <= :final_date';
            $bind = ['final_date' => $dates['final_date']];
        } elseif ($dates['initial_date'] && $dates['final_date']) {
            $date = 'AND invoices.created_at BETWEEN :initial_date AND :final_date';
            $bind = [
                'initial_date' => $dates['initial_date'],
                'final_date' => $dates['final_date'],
            ];
        }

        if ($paymentMethod) {
            $bind['payment_method'] = $paymentMethod;
            $paymentMethod = 'AND invoices.payment_method = :payment_method';
        }

        if ($search) {
            $search = str_replace('.', '', $search);
            $search = str_replace(',', '.', $search);
            $search = (float) $search;
            $bind['search'] = $search;
            $search = 'AND :search IN (invoices.id, invoices.amount)';
        }

        $invoices = DB::select(
            "SELECT
                invoices.id,
                invoices.establishment_id,
                invoices.created_at,
                invoices.updated_at,
                invoices.payment_method,
                invoices.amount,
                invoices.fee,
                invoices.amount - invoices.fee as net_amount,
                invoices.installments
            FROM invoices
            WHERE invoices.establishment_id = :establishment_id
            $date
            $paymentMethod
            $search
            ORDER BY invoices.created_at DESC
            LIMIT :itens_per_page OFFSET :offset",
            $bind + [
                'establishment_id' => $establishmentId,
                'itens_per_page' => $itensPerPage,
                'offset' => $offset,
            ]
        );

        return $invoices;
    }

    public function requestToIuguApi(string $apiToken, array $dadosJson, Invoice $invoice)
    {
        $paymentToken = Http::iugu()->post("payment_token?api_token=$apiToken", [
            'data' => [
                'number' => $dadosJson['card']['number'],
                'verification_value' => $dadosJson['card']['verification_value'],
                'first_name' => $dadosJson['card']['first_name'],
                'last_name' => $dadosJson['card']['last_name'],
                'month' => $dadosJson['card']['month'],
                'year' => $dadosJson['card']['year'],
            ],
            'method' => mb_strtolower($invoice->payment_method->value),
            'account_id' => env('IUGU_ACCOUNT_ID'),
            'test' => App::isProduction() ? false : true,
        ]);
        $tokenInfo = $paymentToken->json();
        if (empty($tokenInfo['id'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Cartão inválido');
        }

        $response = Http::iugu()->post("invoices?api_token=$apiToken", [
            'ensure_workday_due_date' => true,
            'items' => [
                0 => [
                    'description' => 'Transacao ' . $invoice->id,
                    'quantity' => $dadosJson['items'][0]['quantity'],
                    'price_cents' => $invoice->amount,
                ],
            ],
            'payer' => [
                'cpf_cnpj' => '79685531056',
                'name' => 'Teste',
            ],
            'due_date' => date('Y-m-d', strtotime('+' . 1 . ' day')),
            'email' => 'email@gmail.com',
            'max_installments_value' => $dadosJson['max_installments_value'] ?? 1,
        ]);
        $response = $response->json();

        $charged = Http::iugu()->post("charge?api_token=$apiToken", [
            'invoice_id' => $response['id'],
            'token' => $tokenInfo['id'],
            'months' => $dadosJson['months'] ?? 1,
        ]);
        $charged = $charged->json();

        if (!empty($charged['status']) && $charged['status'] === 'captured') {
            $invoice->update(['status' => StatusEnum::PAID]);

            $financialStatments = new FinancialStatements();
            $financialStatments->for = Auth::user()->id;
            $financialStatments->amount = $invoice->amount;
            $financialStatments->type = ItemTypeEnum::ADD_CREDIT;
            $financialStatments->save();

            DB::commit();
        } elseif (empty($charged['status'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, $charged['errors']);
        } else {
            $errorMessage = IuguCreditCardErrorMessages::getErrorMessageByLrCode($charged['LR']);
            throw new HttpException(Response::HTTP_BAD_REQUEST, $errorMessage->message);
        }
    }
}
