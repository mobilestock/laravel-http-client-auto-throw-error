<?php

namespace WcLookPayCC;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use WC_Payment_Gateway_CC;

class CreditCardGateway extends WC_Payment_Gateway_CC
{
    public ClientInterface $httpClient;

    public function __construct()
    {
        $this->id = 'lookpay_cc';
        $this->title = 'LookPay Credit Card';
        $this->method_title = __('Cartão de crédito - LookPay');
        $this->method_description = __('Aceite pagamentos com cartão de crédito usando a LookPay.');
        $this->has_fields = true;

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->debug = filter_var($this->get_option('debug'), FILTER_VALIDATE_BOOLEAN);

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_credit_card_form_start', function (string $gatewayId) {
            if ($gatewayId !== $this->id) {
                return;
            }

            woocommerce_form_field(
                'lookpay_cc-billing-name',
                [
                    'type' => 'text',
                    'label' => __('Nome no cartão'),
                    'required' => true,
                ],
                ''
            );

            $total = WC()->cart->total;
            woocommerce_form_field('lookpay_cc-installments', [
                'type' => 'select',
                'label' => __('Quantidade de parcelas'),
                'required' => true,
                'options' => array_map(
                    fn(int $i): string => $i . 'x - R$' . number_format(round($total / $i, 2), 2, ',', '.'),
                    range(1, 12)
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
            'enabled' => [
                'title' => __('Ativo/Inativo'),
                'type' => 'checkbox',
                'label' => __('Ativar Cartão de Crédito Mobile Stock'),
                'default' => 'yes',
            ],
            'email_instructions' => [
                'title' => __('Instruções por e-mail'),
                'type' => 'textarea',
                'description' => __('Texto exibido no e-mail junto do botão de ver QR Code e do código Copia e Cola.'),
                'default' => __('Clique no botão abaixo para ver os dados de pagamento do seu Pix.'),
                'desc_tip' => true,
            ],
            'advanced_section' => [
                'title' => __('Avançado'),
                'type' => 'title',
                'desc_tip' => false,
            ],
            'debug' => [
                'title' => __('Ativar debug'),
                'type' => 'checkbox',
                'label' => __('Salvar logs das requisições à API'),
                'default' => 'yes',
            ],
            'token' => [
                'title' => __('Token'),
                'type' => 'text',
                'description' => __('Token fornecido pelo Look Pay.'),
                'desc_tip' => true,
            ],
            'lookpay_api_url' => [
                'title' => __('URL da API do Look Pay'),
                'type' => 'text',
                'description' => __('URL da API do Look Pay.'),
                'desc_tip' => true,
            ],
        ];
    }

    public function validate_fields()
    {
        if (empty($_POST['lookpay_cc-billing-name'])) {
            wc_add_notice(__('Por favor, informe o nome no cartão', 'lookpay_cc'), 'error');
        }

        if (empty($_POST['lookpay_cc-card-number'])) {
            wc_add_notice(__('Por favor, informe o número do cartão', 'lookpay_cc'), 'error');
        }

        if (empty($_POST['lookpay_cc-card-expiry'])) {
            wc_add_notice(__('Por favor, informe a data de vencimento do cartão', 'lookpay_cc'), 'error');
        }

        if (empty($_POST['lookpay_cc-card-cvc'])) {
            wc_add_notice(__('Por favor, informe o CVC', 'lookpay_cc'), 'error');
        }
    }

    /**
     * @param string $order_id
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $name = explode(' ', $_POST['lookpay_cc-billing-name']);

        $firstName = array_shift($name);
        $lastName = implode(' ', $name);

        $request = new Request(
            'POST',
            '/v1/invoices?api_token=' . $this->get_option('token'),
            [
                'Content-Type' => 'application/json',
            ],
            json_encode([
                'card' => [
                    [
                        'number' => preg_replace('[^0-9]', '', $_POST['lookpay_cc-card-number']),
                        'verification_value' => $_POST['lookpay_cc-card-cvc'],
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'month' => mb_substr($_POST['lookpay_cc-card-expiry'], 0, 2),
                        'year' => mb_substr($_POST['lookpay_cc-card-expiry'], -4),
                    ],
                ],
                'method' => 'CREDIT_CARD',
                'items' => [
                    [
                        'quantity' => 1,
                        'price_cents' => round($order->get_total() * 100),
                    ],
                ],
                'max_installments_value' => 12,
                'months' => $_POST['lookpay_cc-installments'] + 1,
            ])
        );

        $lookpayId = $this->httpClient->sendRequest($request);
        $lookpayId = $lookpayId->getBody()->getContents();
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
