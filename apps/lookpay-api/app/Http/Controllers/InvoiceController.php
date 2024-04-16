<?php

namespace App\Http\Controllers;

use App\Enum\Invoice\ItemTypeEnum;
use App\Enum\Invoice\PaymentMethodsEnum;
use App\Models\Invoice;
use App\Models\InvoicesItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class InvoiceController
{
    public function createInvoice()
    {
        $dadosJson = Request::validate([
            'card' => ['required', 'array', 'min:6', 'max:6'],
            'card.number' => ['required', 'numeric', 'digits:16'],
            'card.verification_value' => ['required', 'numeric', 'digits_between:3,4'],
            'card.first_name' => ['required', 'string'],
            'card.last_name' => ['required', 'string'],
            'card.month' => ['required', 'numeric', 'gte:0', 'lte:12'],
            'card.year' => ['required', 'numeric'],
            'method' => ['required', Rule::enum(PaymentMethodsEnum::class)],
            'reference_id' => ['sometimes', 'required', 'max:26'],
            'items' => ['required', 'array', 'size:1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.price_cents' => ['required', 'numeric', 'gt:0'],
            'max_installments_value' => ['sometimes', 'required', 'numeric', 'gte:1'],
            'months' => ['sometimes', 'required', 'numeric', 'gte:1'],
        ]);
        $fees = (array) json_decode(Auth::user()->fees);
        $fee = ($fees[$dadosJson['months']] / 100) * $dadosJson['items'][0]['price_cents'];

        $invoice = new Invoice();
        $invoice->establishment_id = Auth::user()->id;
        $invoice->payment_method = $dadosJson['method'];
        $invoice->amount = $dadosJson['items'][0]['price_cents'];
        $invoice->fee = $fee;
        $invoice->installments = $dadosJson['months'] ?? 1;
        if (!empty($dadosJson['reference_id'])) {
            $invoice->reference_id = $dadosJson['reference_id'];
        }
        $invoice->save();

        foreach ($dadosJson['items'] as $commission) {
            $InvoicesItem = new InvoicesItem();
            $InvoicesItem->invoice_id = $invoice->id;
            $InvoicesItem->type = ItemTypeEnum::ADD_CREDIT;
            $InvoicesItem->amount = $commission['price_cents'];
            $InvoicesItem->save();
        }

        $invoice->requestToIuguApi($dadosJson, $invoice);

        DB::commit();
        return [
            'lookpay_id' => $invoice->id,
        ];
    }

    public function getInvoicesDetails()
    {
        $request = Request::validate([
            'page' => ['sometimes', 'required', 'numeric', 'gte:1'],
            'initial_date' => ['sometimes', 'required', 'date'],
            'final_date' => ['sometimes', 'required', 'date'],
            'payment_method' => ['sometimes', 'required', Rule::enum(PaymentMethodsEnum::class)],
            'search' => ['sometimes', 'required', 'string'],
        ]);

        $invoices = Invoice::getInvoicesDetails($request);

        return $invoices;
    }
}
