<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use WcLookPayCC\CreditCardGateway;

class LookpayCreditCardTest extends TestCase
{
    public function testFakeException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recusado automaticamente em analise antifraude');

        $mockHandler = new MockHandler([new Exception('Recusado automaticamente em analise antifraude')]);
        $handlerStack = HandlerStack::create($mockHandler);

        $creditCardGateway = new CreditCardGateway();
        $creditCardGateway->httpClient = new Client(['handler' => $handlerStack]);
        $creditCardGateway->process_payment(1);
    }

    public function testFakeSuccess()
    {
        $mockHandler = new MockHandler([new Response(200, ['lookpay_id' => 5], 10)]);
        $handlerStack = HandlerStack::create($mockHandler);

        $creditCardGateway = new CreditCardGateway();
        $creditCardGateway->httpClient = new Client(['handler' => $handlerStack]);
        $response = $creditCardGateway->process_payment(1);

        $this->assertEquals(10, $response['redirect']);
    }
}
