<?php

use App\Enum\Invoice\PaymentMethodsEnum;
use App\Models\FinancialStatements;
use App\Models\Invoice;
use App\Models\Model;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Auth;

class InvoiceTest extends TestCase
{
    public function testInvalidCreditCard(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Cartão inválido');

        $invoice = new Invoice();
        $dadosJson = [
            'card' => [
                'number' => '4242424242424241',
                'verification_value' => '123',
                'first_name' => 'teste',
                'last_name' => 'teste',
                'month' => 12,
                'year' => 2024,
            ],
        ];

        $invoice = new Invoice();
        $invoice->payment_method = PaymentMethodsEnum::CREDIT_CARD;

        Auth::setUser(
            new GenericUser([
                'iugu_token_live' => '6dc259f9-c505-11ee-94f1-0242ac120002',
            ])
        );

        Http::fake(function () {
            return Http::response(
                json_encode([
                    'id' => null,
                ])
            );
        });

        $invoice->requestToIuguApi($dadosJson, $invoice);
    }

    public function testPaymentGoesWrong(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Recusado automaticamente em analise antifraude');

        $pdoMock = $this->createMock(PDO::class);
        app()->bind(PDO::class, fn() => $pdoMock);

        $DatabaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $connectionMock = $this->createPartialMock(MySqlConnection::class, ['select']);
        $connectionMock->__construct($pdoMock);
        $connectionMock->method('select')->willReturn([
            [
                'message' => 'Recusado automaticamente em analise antifraude',
            ],
        ]);
        $DatabaseManagerMock->method('connection')->willReturn($connectionMock);

        Auth::setUser(
            new GenericUser([
                'iugu_token_live' => '6dc259f9-c505-11ee-94f1-0242ac120002',
                'name' => 'random_name',
            ])
        );

        DB::swap($DatabaseManagerMock);
        Model::setConnectionResolver(app('db'));

        $invoice = new Invoice();
        $invoice->payment_method = PaymentMethodsEnum::CREDIT_CARD;
        $invoice->id = 1;

        $dadosJson = [
            'card' => [
                'number' => '4242424242424242',
                'verification_value' => '123',
                'first_name' => 'teste',
                'last_name' => 'teste',
                'month' => 12,
                'year' => 2026,
            ],
            'items' => [
                [
                    'description' => 'Teste',
                    'quantity' => 1,
                    'price_cents' => 1000,
                ],
            ],
        ];

        Http::fake([
            'https://api.iugu.com/v1/*' => Http::sequence()
                ->push(
                    json_encode([
                        'id' => '02c7180f-ed2a-4e08-986f-47b0055aedfa',
                    ]),
                    200
                )
                ->push(
                    json_encode([
                        'id' => '02c7180f-ed2a-4e08-986f-47b0055aedfa',
                    ]),
                    200
                )
                ->whenEmpty(Http::response())
                ->push(
                    json_encode([
                        'status' => 'no_captured',
                        'LR' => 'AF02',
                    ]),
                    200
                ),
        ]);

        $invoice->requestToIuguApi($dadosJson, $invoice);
    }

    public function testValidCreditCard(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        app()->bind(PDO::class, fn() => $pdoMock);

        $databaseManagerMock = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $connectionMock = $this->createPartialMock(Connection::class, ['insert']);
        $connectionMock->__construct($pdoMock);
        $connectionMock->method('insert');

        $databaseManagerMock->method('connection')->willReturn($connectionMock);

        Auth::setUser(
            new GenericUser([
                'id' => '6dc259f9-c505-11ee-94f1-0242ac120002',
                'name' => 'random_name',
                'iugu_token_live' => '6dc259f9-c505-11ee-94f1-0242ac120002',
            ])
        );

        DB::swap($databaseManagerMock);
        Model::setConnectionResolver(app('db'));

        $invoice = new Invoice();
        $dadosJson = [
            'card' => [
                'number' => '4242424242424242',
                'verification_value' => '123',
                'first_name' => 'teste',
                'last_name' => 'teste',
                'month' => 12,
                'year' => 2026,
            ],
            'items' => [
                [
                    'description' => 'Teste',
                    'quantity' => 1,
                    'price_cents' => 1000,
                ],
            ],
        ];

        $invoice = new Invoice();
        $invoice->payment_method = PaymentMethodsEnum::CREDIT_CARD;
        $invoice->id = '02c7180f-ed2a-4e08-986f-47b0055aedfa';
        $invoice->amount = 1000;

        Http::fake([
            'https://api.iugu.com/v1/*' => Http::sequence()
                ->push(
                    json_encode([
                        'id' => '02c7180f-ed2a-4e08-986f-47b0055aedfa',
                    ]),
                    200
                )
                ->push(
                    json_encode([
                        'id' => '02c7180f-ed2a-4e08-986f-47b0055aedfa',
                    ]),
                    200
                )
                ->push(
                    json_encode([
                        'status' => 'captured',
                    ]),
                    200
                ),
        ]);
        $invoice->requestToIuguApi($dadosJson, $invoice);
        FinancialStatements::creating([
            self::class,
            function () {
                return false;
            },
        ]);

        $this->assertTrue(true);
    }
}
