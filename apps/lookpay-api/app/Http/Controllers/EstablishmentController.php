<?php

namespace App\Http\Controllers;

use App\Models\Establishment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class EstablishmentController extends Controller
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
        $invoicesModel = new Invoice();
        $methods = $invoicesModel->paymentMethods();

        return $methods;
    }
}
