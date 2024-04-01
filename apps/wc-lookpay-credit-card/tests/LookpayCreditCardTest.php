<?php

use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use WcLookPayCC\CreditCardGateway;

class LookpayCreditCardTest extends TestCase
{
    public function testFakeException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recusado automaticamente em analise antifraude');

        $client = $this->getMockBuilder(Client::class)
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(new Exception('Recusado automaticamente em analise antifraude'));

        $class = new CreditCardGateway();
        $class->httpClient = $client;
        $class->process_payment(1);
    }

    public function testValidCreditCard()
    {
        $client = new Client();

        $response = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($mockStream);
        $mockStream->method('getContents')->willReturn('{"lookpay_id": 10}');

        $client->addResponse($response);

        $creditCardGateway = new CreditCardGateway();
        $creditCardGateway->httpClient = $client;
        $response = $creditCardGateway->process_payment(1);

        $lookpayId = $response['redirect'];
        $this->assertEquals(10, $lookpayId->meta_data['lookpay_id']);
    }
}
