<?php

namespace App\Models;

use App\Enum\Invoice\InvoiceItemTypeEnum;
use App\Enum\Invoice\InvoiceStatusEnum;
use App\Enum\Invoice\PaymentMethodsEnum;
use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * App\Models\Invoice
 *
 * @property string $id
 * @property string $establishment_id
 * @property PaymentMethodsEnum $payment_method
 * @property float $installments
 * @property int $amount
 * @property int $fee
 * @property ?string $payment_provider_invoice_id
 * @property ?string $establishment_order_id
 * @property InvoiceStatusEnum $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Invoice extends Model
{
    protected $fillable = [
        'id',
        'establishment_id',
        'payment_method',
        'amount',
        'fee',
        'payment_provider_invoice_id',
        'establishment_order_id',
        'status',
    ];
    protected $casts = ['payment_method' => PaymentMethodsEnum::class, 'status' => InvoiceStatusEnum::class];
    protected static function boot(): void
    {
        parent::boot();

        self::created(function (self $model) {
            $log = new InvoicesLog();
            $log->payload = $model;
            $log->save();
        });

        self::updated(function (self $model) {
            $log = new InvoicesLog();
            $log->payload = $model;
            $log->save();
        });
    }

    /**
     * @issue https://github.com/mobilestock/backend/issues/48
     */
    public static function searchInvoices(
        int $page,
        ?string $initialDate,
        ?string $finalDate,
        ?string $paymentMethod,
        ?string $search
    ): array {
        $itensPerPage = 50;
        $offset = ($page - 1) * $itensPerPage;
        $bind = [];
        $whereSql = 'invoices.establishment_id = :establishment_id ';

        switch (true) {
            case $initialDate && !$finalDate:
                $whereSql .= 'AND DATE(invoices.created_at) >= :initial_date ';
                $bind = ['initial_date' => $initialDate];
                break;
            case $finalDate && !$initialDate:
                $whereSql .= 'AND DATE(invoices.created_at) <= :final_date ';
                $bind = ['final_date' => $finalDate];
                break;
            case $initialDate && $finalDate:
                $whereSql .=
                    'AND DATE(invoices.created_at) >= :initial_date AND DATE(invoices.created_at) <= :final_date ';
                $bind = [
                    'initial_date' => $initialDate,
                    'final_date' => $finalDate,
                ];
                break;
        }

        if ($paymentMethod) {
            $bind['payment_method'] = $paymentMethod;
            $whereSql .= 'AND invoices.payment_method = :payment_method ';
        }

        if ($search) {
            $search = str_replace(['.', ','], '', $search);
            $bind['search'] = "%$search%";

            $whereSql .= " AND CONCAT_WS(
                    ' ',
                    invoices.amount,
                    invoices.id
                ) LIKE :search ";
        }

        $invoices = DB::select(
            "SELECT
                invoices.id,
                invoices.establishment_id,
                invoices.created_at,
                invoices.updated_at,
                invoices.payment_method,
                invoices.amount / 100 as amount,
                invoices.fee / 100 as fee,
                (invoices.amount - invoices.fee) / 100 as net_amount,
                invoices.installments
            FROM invoices
            WHERE $whereSql
            ORDER BY invoices.created_at DESC
            LIMIT :itens_per_page OFFSET :offset",
            $bind + [
                'establishment_id' => Auth::user()->id,
                'itens_per_page' => $itensPerPage,
                'offset' => $offset,
            ]
        );

        return $invoices;
    }

    public function requestToIuguApi(array $card, int $numberOfMonths, Invoice $invoice): void
    {
        $apiToken = Auth::user()->iugu_token_live;
        $paymentToken = Http::iugu()->post("payment_token?api_token=$apiToken", [
            'data' => $card,
            'method' => mb_strtolower($invoice->payment_method->value),
            'account_id' => env('IUGU_ACCOUNT_ID'),
            'test' => !App::isProduction(),
        ]);
        $tokenInfo = $paymentToken->json();
        if (empty($tokenInfo['id'])) {
            throw new BadRequestHttpException('Cartão inválido');
        }

        $response = Http::iugu()
            ->post("invoices?api_token=$apiToken", [
                'ensure_workday_due_date' => true,
                'items' => [
                    [
                        'description' => 'Transacao ' . $invoice->id,
                        'quantity' => 1,
                        'price_cents' => $invoice->amount,
                    ],
                ],
                'payer' => [
                    'cpf_cnpj' => '79685531056',
                    'name' => Auth::user()->name,
                ],
                'due_date' => (new DateTime())->modify('+ 1 day')->format('Y-m-d'),
                'email' => 'email@gmail.com',
                'max_installments_value' => 12,
            ])
            ->throw();

        $response = $response->json();
        $invoice->payment_provider_invoice_id = $response['id'];

        $charged = Http::iugu()->post("charge?api_token=$apiToken", [
            'invoice_id' => $response['id'],
            'token' => $tokenInfo['id'],
            'months' => $numberOfMonths,
        ]);

        $charged = $charged->json();

        if (!empty($charged['status']) && $charged['status'] === 'captured') {
            $invoice->update(['status' => InvoiceStatusEnum::PAID]);

            $financialStatments = new FinancialStatements();
            $financialStatments->establishment_id = Auth::user()->id;
            $financialStatments->amount = $invoice->amount - $invoice->fee;
            $financialStatments->type = InvoiceItemTypeEnum::ADD_CREDIT;
            $financialStatments->save();
        } elseif (empty($charged['status'])) {
            throw new BadRequestHttpException($charged['errors']);
        } else {
            $errorMessage = IuguCreditCardErrorMessage::getErrorMessageByLrCode($charged['LR']);
            throw new BadRequestHttpException($errorMessage->message);
        }
    }
}
