<?php

namespace App\Http\Controllers;

use App\Enum\Invoice\PaymentMethodsEnum;
use App\Models\Establishment;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class EstablishmentController
{
    public function searchUser(string $phoneNumber)
    {
        $establishments = Establishment::getEstablishmentByPhoneNumber($phoneNumber);

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

        return [
            'id' => $user['id'],
            'token' => $user['token'],
            'name' => $user['name'],
        ];
    }

    public function getPaymentMethods()
    {
        $methods = array_column(PaymentMethodsEnum::cases(), 'value');

        return $methods;
    }
}
