<?php

namespace App\Http\Controllers;

use App\Enum\Invoice\PaymentMethodsEnum;
use App\Models\Establishment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class EstablishmentController
{
    /**
     * @issue https://github.com/mobilestock/backend/issues/43
     */
    public function getEstablishmentsByPhoneNumber()
    {
        Request::validate([
            'phone_number' => ['required', 'string'],
        ]);
        $phoneNumber = preg_replace('/[^0-9]/', '', Request::input('phone_number'));

        $establishments = Establishment::getEstablishmentsByPhoneNumber($phoneNumber);

        if (empty($establishments)) {
            throw new NotFoundHttpException('Telefone não encontrado');
        }

        return $establishments;
    }

    public function login()
    {
        $request = Request::validate([
            'establishment_id' => ['required', 'uuid'],
            'password' => ['required', 'string'],
        ]);

        $user = Establishment::authentication($request['establishment_id'], $request['password']);

        if (empty($user) || !password_verify($request['password'], $user['password'])) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        return Arr::only($user, ['id', 'token', 'name']);
    }

    public function getPaymentMethods()
    {
        $methods = PaymentMethodsEnum::cases();

        return $methods;
    }
}
