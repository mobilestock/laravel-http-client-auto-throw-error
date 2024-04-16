<?php

namespace App\Models;

use App\Enum\Invoice\ItemTypeEnum;
use App\Enum\Invoice\PaymentMethodsEnum;
use App\Enum\Invoice\StatusEnum;
use DateInterval;
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
 * @property float $amount
 * @property float $fee
 * @property ?string $external_id
 * @property ?string $reference_id
 * @property StatusEnum $status
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
        'external_id',
        'reference_id',
        'status',
    ];
    protected $casts = ['payment_method' => PaymentMethodsEnum::class, 'status' => StatusEnum::class];
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

    public static function getInvoicesDetails(
        int $page,
        ?string $initialDate,
        ?string $finalDate,
        ?string $paymentMethod,
        ?string $search
    ): array {
        $itensPerPage = 50;
        $offset = ($page - 1) * $itensPerPage;
        $bind = [];

        $dates = [
            'initial_date' => $data['initial_date'] ?? null,
            'final_date' => $data['final_date'] ?? null,
        ];
        $paymentMethod = $data['payment_method'] ?? null;
        $search = $data['search'] ?? null;

        $dateSql = '';
        if ($dates['initial_date'] && !$dates['final_date']) {
            $dateSql = 'AND invoices.created_at >= :initial_date';
            $bind = ['initial_date' => $dates['initial_date']];
        } elseif ($dates['final_date'] && !$dates['initial_date']) {
            $dateSql = 'AND invoices.created_at <= :final_date';
            $bind = ['final_date' => $dates['final_date']];
        } elseif ($dates['initial_date'] && $dates['final_date']) {
            $dateSql = 'AND invoices.created_at BETWEEN :initial_date AND :final_date';
            $bind = [
                'initial_date' => $dates['initial_date'],
                'final_date' => $dates['final_date'],
            ];
        }

        if ($paymentMethod) {
            $bind['payment_method'] = $paymentMethod;
            $paymentMethodSql = 'AND invoices.payment_method = :payment_method';
        }

        if ($search) {
            $bind['search'] = (float) str_replace(['.', ','], ['', '.'], $search);
            $searchSql = 'AND :search IN (invoices.id, invoices.amount)';
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
            $dateSql
            $paymentMethodSql
            $searchSql
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

    public function requestToIuguApi(array $dadosJson, Invoice $invoice)
    {
        $apiToken = Auth::user()->iugu_token_live;
        $paymentToken = Http::iugu()->post("payment_token?api_token=$apiToken", [
            'data' => $dadosJson['card'],
            'method' => mb_strtolower($invoice->payment_method->value),
            'account_id' => env('IUGU_ACCOUNT_ID'),
            'test' => !App::isProduction(),
        ]);
        $tokenInfo = $paymentToken->json();
        if (empty($tokenInfo['id'])) {
            throw new BadRequestHttpException('Cartão inválido');
        }

        $response = Http::iugu()->post("invoices?api_token=$apiToken", [
            'ensure_workday_due_date' => true,
            'items' => [
                [
                    'description' => 'Transacao ' . $invoice->id,
                    'quantity' => $dadosJson['items'][0]['quantity'],
                    'price_cents' => $invoice->amount,
                ],
            ],
            'payer' => [
                'cpf_cnpj' => '79685531056',
                'name' => Auth::user()->name,
            ],
            'due_date' => (new DateTime())->add(DateInterval::createFromDateString('+ 1 day'))->format('Y-m-d'),
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
        } elseif (empty($charged['status'])) {
            throw new BadRequestHttpException($charged['errors']);
        } else {
            $errorMessage = IuguCreditCardErrorMessage::getErrorMessageByLrCode($charged['LR']);
            throw new BadRequestHttpException($errorMessage->message);
        }
    }
}
