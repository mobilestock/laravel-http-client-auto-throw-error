<?php

namespace App\Http\Controllers;

use App\Enum\Invoice\InvoiceItemTypeEnum;
use App\Enum\Invoice\PaymentMethodsEnum;
use App\Models\Invoice;
use App\Models\InvoicesItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InvoiceController
{
    public function createInvoice()
    {
        DB::beginTransaction();
        $data = Request::validate([
            'card' => ['required', 'array', 'min:6', 'max:6'],
            'card.number' => ['required', 'numeric', 'digits:16'],
            'card.verification_value' => ['required', 'numeric', 'digits_between:3,4'],
            'card.first_name' => ['required', 'string'],
            'card.last_name' => ['required', 'string'],
            'card.month' => ['required', 'numeric', 'gte:0', 'lte:12'],
            'card.year' => ['required', 'numeric'],
            'method' => ['required', Rule::enum(PaymentMethodsEnum::class)],
            'establishment_order_id' => ['sometimes', 'required', 'max:26', 'unique:invoices,establishment_order_id'],
            'items' => ['required', 'array', 'size:1'],
            'items.*.price_cents' => ['required', 'numeric', 'gt:0', 'integer'],
            'months' => ['required', 'numeric', 'gte:1'],
        ]);

        $numberOfMonths = $data['months'];
        $amount = $data['items'][0]['price_cents'];
        $fees = json_decode(Auth::user()->fees, true);
        $fee = ($fees[$numberOfMonths - 1] / 100) * $amount;

        $invoice = new Invoice();
        $invoice->establishment_id = Auth::user()->id;
        $invoice->payment_method = $data['method'];
        $invoice->amount = $amount;
        $invoice->fee = $fee;
        $invoice->installments = $data['months'];
        if (!empty($data['establishment_order_id'])) {
            $invoice->establishment_order_id = $data['establishment_order_id'];
        }

        $invoice->save();

        $invoiceItem = new InvoicesItem();
        $invoiceItem->invoice_id = $invoice->id;
        $invoiceItem->type = InvoiceItemTypeEnum::ADD_CREDIT;
        $invoiceItem->amount = $amount;
        $invoiceItem->save();

        $invoice->requestToIuguApi($data['card'], $numberOfMonths, $invoice);

        DB::commit();
        return [
            'lookpay_id' => $invoice->id,
        ];
    }

    public function searchInvoices()
    {
        $request = Request::validate([
            'page' => ['required', 'numeric', 'gte:1'],
            'initial_date' => ['sometimes', 'required', 'date'],
            'final_date' => ['sometimes', 'required', 'date'],
            'payment_method' => ['sometimes', 'required', Rule::enum(PaymentMethodsEnum::class)],
            'search' => ['sometimes', 'required', 'string'],
        ]);

        $invoices = Invoice::searchInvoices(
            $request['page'],
            $request['initial_date'] ?? null,
            $request['final_date'] ?? null,
            $request['payment_method'] ?? null,
            $request['search'] ?? null
        );

        return $invoices;
    }
}
