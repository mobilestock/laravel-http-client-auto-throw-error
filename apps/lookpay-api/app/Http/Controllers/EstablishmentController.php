<?php

namespace App\Http\Controllers;

use App\Enum\Invoice\PaymentMethodsEnum;
use App\Models\Establishment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class EstablishmentController
{
    /**
     * @issue https://github.com/mobilestock/backend/issues/38
     */
    public function getEstablishmentsByPhoneNumber()
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', Request::input('phone_number'));

        $establishments = Establishment::getEstablishmentsByPhoneNumber($phoneNumber);

        return $establishments;
    }

    public function login()
    {
        $request = Request::validate([
            'establishment_id' => ['required'],
            'password' => ['required', 'string'],
        ]);

        $user = Establishment::authentication($request['establishment_id'], $request['password']);

        if (empty($user)) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        return Arr::only($user, ['id', 'token', 'name']);
    }

    public function getPaymentMethods()
    {
        $methods = array_column(PaymentMethodsEnum::cases(), 'value');

        return $methods;
    }
}
