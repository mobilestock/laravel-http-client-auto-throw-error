<?php

namespace WcLookPayCC;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use WC_Order_Item_Fee;
use WC_Payment_Gateway_CC;

class CreditCardGateway extends WC_Payment_Gateway_CC
{
    public ClientInterface $httpClient;

    public function __construct()
    {
        $this->id = 'lookpay_cc';
        $this->title = 'LookPay Credit Card';
        $this->method_title = 'Cartão de crédito - LookPay';
        $this->method_description = 'Aceite pagamentos com cartão de crédito usando a LookPay.';
        $this->has_fields = true;

        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_credit_card_form_start', function (string $gatewayId) {
            if ($gatewayId !== $this->id) {
                return;
            }

            woocommerce_form_field(
                'lookpay_cc-billing-name',
                [
                    'type' => 'text',
                    'label' => 'Nome no cartão',
                    'required' => true,
                ],
                ''
            );

            $cardFees = json_decode($this->get_option('card_fees') ?? '[]', true);
            $total = WC()->cart->total;
            woocommerce_form_field('lookpay_cc-installments', [
                'type' => 'select',
                'label' => 'Quantidade de parcelas',
                'required' => true,
                'options' => array_map(
                    function (float $fee, int $index) use ($total): string {
                        $index++;
                        $percentage = 1 + $fee / 100;
                        $value = round($total * $percentage, 2);
                        $value = round($value / $index, 2);
                        $value = number_format($value, 2, ',', '.');

                        return "{$index}x - R\$$value";
                    },
                    $cardFees,
                    array_keys($cardFees)
                ),
            ]);
        });

        $this->httpClient = new Client([
            'base_uri' => $this->get_option('lookpay_api_url'),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_option('token'),
            ],
        ]);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'token' => [
                'title' => 'Token',
                'type' => 'text',
                'description' => 'Token fornecido pelo Look Pay.',
                'desc_tip' => true,
            ],
            'lookpay_api_url' => [
                'title' => 'URL da API do Look Pay',
                'type' => 'text',
                'description' => 'URL da API do Look Pay.',
                'desc_tip' => true,
            ],
            'card_fees' => [
                'desc_tip' => true,
                'description' =>
                    'Cada valor representa a porcentagem de juros cobrada em cada parcela, e o valor deve ser um array JSON. Exemplo: [0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6]',
                'placeholder' => json_encode(array_map(fn(float $fee): float => round($fee, 2), range(0, 2, 0.3))),
                'required' => true,
                'title' => 'Percentual de acréscimo por parcela',
                'type' => 'text',
            ],
        ];
    }

    public function validate_fields()
    {
        $valid = true;
        if (empty($_POST['lookpay_cc-billing-name'])) {
            wc_add_notice('Por favor, informe o nome no cartão', 'error');
            $valid = false;
        }

        if (empty($_POST['lookpay_cc-card-number'])) {
            wc_add_notice('Por favor, informe o número do cartão', 'error');
            $valid = false;
        }

        if (empty($_POST['lookpay_cc-card-expiry'])) {
            wc_add_notice('Por favor, informe a data de vencimento do cartão', 'error');
            $valid = false;
        }

        if (empty($_POST['lookpay_cc-card-cvc'])) {
            wc_add_notice('Por favor, informe o CVC', 'error');
            $valid = false;
        }

        return $valid;
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $installments = $_POST['lookpay_cc-installments'];
        $fee = json_decode($this->get_option('card_fees'), true)[$installments];
        $startingTotal = $order->get_total();

        $total = $startingTotal * (1 + $fee / 100);
        $mounths = $installments + 1;
        $installmentValue = round($total / $mounths, 2);
        $totalPaid = $installmentValue * $mounths;
        $feeValuePaid = round($totalPaid - $startingTotal, 2);

        $orderItemFee = new WC_Order_Item_Fee();
        $orderItemFee->set_name('Acréscimo cartão');
        $orderItemFee->set_amount($feeValuePaid);
        $orderItemFee->set_tax_status('none');
        $orderItemFee->set_total($feeValuePaid);

        $order->add_item($orderItemFee);
        $order->calculate_totals();
        $order->save();

        $total = $order->get_total();

        $name = explode(' ', $_POST['lookpay_cc-billing-name']);

        $firstName = array_shift($name);
        $lastName = implode(' ', $name);

        [$mes, $ano] = explode(' / ', $_POST['lookpay_cc-card-expiry']);

        $request = new Request(
            'POST',
            '/v1/invoices?api_token=' . $this->get_option('token'),
            [
                'Content-Type' => 'application/json',
            ],
            json_encode([
                'card' => [
                    'number' => preg_replace('/[^0-9]/', '', $_POST['lookpay_cc-card-number']),
                    'verification_value' => $_POST['lookpay_cc-card-cvc'],
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'month' => $mes,
                    'year' => $ano,
                ],
                'method' => 'CREDIT_CARD',
                'items' => [
                    [
                        'price_cents' => round($total * 100),
                    ],
                ],
                'months' => $installments + 1,
                'establishment_order_id' => uniqid('wc-') . '--' . $order->get_id(),
            ])
        );

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            $uri = $request->getUri()->withQuery('api_token=********');
            throw RequestException::create($request->withUri($uri), $response);
        }

        $lookpayId = $response->getBody()->getContents();
        $lookpayId = json_decode($lookpayId, true)['lookpay_id'];

        $order->add_meta_data('lookpay_id', $lookpayId, true);
        $order->payment_complete();
        $order->save();

        WC()->cart->empty_cart();

        return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}
