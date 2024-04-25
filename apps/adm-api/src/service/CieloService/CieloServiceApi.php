<?php

namespace MobileStock\service\CieloService;

use Cielo\API30\Merchant;

use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\Payment;
use Cielo\API30\Ecommerce\CreditCard;

use Cielo\API30\Ecommerce\Request\CieloRequestException;
use MobileStock\helper\Globals;
use MobileStock\helper\LoggerFactory;

class CieloServiceApi 
{
    private $merchantId;
    private $merchantKey;
    private $idTransacao;
    private $nomePagador;
    private $valorLiquido;
    private $dadosCartao;
    private $numParcela;
    private $validacaoCartao;
    public $mensamge_erro;


    public function __construct(array $meioDePagamento)
    {   
        $this->merchantId = $_ENV['DADOS_PAGAMENTO_MERCHANTID'];
        $this->merchantKey = $_ENV['DADOS_PAGAMENTO_MERCHANTKEY'];
        $this->idTransacao = $meioDePagamento['id_transacao'];
        $this->nomePagador = $meioDePagamento['data']['holderName'];
        $this->valorLiquido = $meioDePagamento['montante'];
        $this->dadosCartao = $meioDePagamento['data'];
        $this->numParcela = $meioDePagamento['parcelas'];
    }

    public function consultaBandeiraCartao()
    {
        $consulta = "https://apiquery.cieloecommerce.cielo.com.br/1/cardBin/".substr($this->dadosCartao['creditCardNumber'],0,6);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $consulta,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'merchantId: '.$this->merchantId,
            'MerchantKey: '.$this->merchantKey
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $this->validacaoCartao = $response;
    } 
   
    public function pagamentoCielo(){
        $retorno=[];
        $environment = $environment = $_ENV['AMBIENTE'] === 'producao' ? Environment::production() : Environment::sandbox();
        $merchant = new Merchant($this->merchantId, $this->merchantKey);
        $sale = new Sale($this->idTransacao);
        $customer = $sale->customer($this->nomePagador);
        $payment = $sale->payment($this->valorLiquido,$this->numParcela);
        $bandeira =  json_decode($this->validacaoCartao);
        switch ($bandeira->Provider) {
            case 'MASTERCARD':
                $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                ->creditCard($this->dadosCartao['secureCode'], CreditCard::MASTERCARD)
                ->setExpirationDate($this->dadosCartao['expirationDateMonth']."/".$this->dadosCartao['expirationDateYear'])
                ->setCardNumber($this->dadosCartao['creditCardNumber'])
                ->setHolder($this->dadosCartao['holderName']);
                break;
                case 'AMEX':
                    $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                    ->creditCard($this->dadosCartao['secureCode'], CreditCard::AMEX)
                    ->setExpirationDate($this->dadosCartao['expirationDateMonth']."/".$this->dadosCartao['expirationDateYear'])
                    ->setCardNumber($this->dadosCartao['creditCardNumber'])
                    ->setHolder($this->dadosCartao['holderName']);
                    break;
                case 'DISCOVER':
                    $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                    ->creditCard($this->dadosCartao['secureCode'], CreditCard::DISCOVER)
                    ->setExpirationDate($this->dadosCartao['expirationDateMonth']."/".$this->dadosCartao['expirationDateYear'])
                    ->setCardNumber($this->dadosCartao['creditCardNumber'])
                    ->setHolder($this->dadosCartao['holderName']);
                    break;
                case 'ELO':
                    $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                    ->creditCard($this->dadosCartao['secureCode'], CreditCard::ELO)
                    ->setExpirationDate($this->dadosCartao['expirationDateMonth']."/".$this->dadosCartao['expirationDateYear'])
                    ->setCardNumber($this->dadosCartao['creditCardNumber'])
                    ->setHolder($this->dadosCartao['holderName']);
                    break;
                case 'AURA':
                    $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                    ->creditCard($this->dadosCartao['secureCode'], CreditCard::AURA)
                    ->setExpirationDate($this->dadosCartao['expirationDateMonth']."/".$this->dadosCartao['expirationDateYear'])
                    ->setCardNumber($this->dadosCartao['creditCardNumber'])
                    ->setHolder($this->dadosCartao['holderName']);
                    break;
                case 'JCB':
                    $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                    ->creditCard($this->dadosCartao['secureCode'], CreditCard::JCB)
                    ->setExpirationDate($this->dadosCartao['expirationDateMonth']."/".$this->dadosCartao['expirationDateYear'])
                    ->setCardNumber($this->dadosCartao['creditCardNumber'])
                    ->setHolder($this->dadosCartao['holderName']);
                    break;
                case 'DINERS':
                    $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                    ->creditCard($this->dadosCartao['secureCode'], CreditCard::DISCOVER)
                    ->setExpirationDate($this->dadosCartao['expirationDateMonth']."/".$this->dadosCartao['expirationDateYear'])
                    ->setCardNumber($this->dadosCartao['creditCardNumber'])
                    ->setHolder($this->dadosCartao['holderName']);
                    break;
                case 'HIPERCARD':
                    $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                    ->creditCard($this->dadosCartao['secureCode'], CreditCard::HIPERCARD)
                    ->setExpirationDate($this->dadosCartao['expirationDateMonth']."/".$this->dadosCartao['expirationDateYear'])
                    ->setCardNumber($this->dadosCartao['creditCardNumber'])
                    ->setHolder($this->dadosCartao['holderName']);
                    break;

            default:
                $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                ->creditCard($this->dadosCartao['secureCode'], CreditCard::VISA)
                ->setExpirationDate($this->dadosCartao['expirationDateMonth']."/".$this->dadosCartao['expirationDateYear'])
                ->setCardNumber($this->dadosCartao['creditCardNumber'])
                ->setHolder($this->dadosCartao['holderName']);
                break;
        }
       

        // Crie o pagamento na Cielo
        try {
            // Configure o SDK com seu merchant e o ambiente apropriado para criar a venda
            $logger = LoggerFactory::arquivo('logs_requisicao_cielo.log');
            $sale = (new CieloEcommerce($merchant, $environment, $logger))->createSale($sale);

            // Com a venda criada na Cielo, já temos o ID do pagamento, TID e demais
            // dados retornados pela Cielo
            $pagamento['paymentId'] = $sale->getPayment()->getPaymentId();
            $pagamento['returnCode'] = $sale->getPayment()->getReturnCode();
            $pagamento['returnMessage'] = $sale->getPayment()->getReturnMessage(); 
            $pagamento['status'] =  $sale->getPayment()->getStatus();
            if ($pagamento['returnCode'] === "00" || $pagamento['returnCode'] === "4" || $pagamento['returnCode'] === "6") {
                $captura = (new CieloEcommerce($merchant, $environment))->captureSale($pagamento['paymentId'], $this->valorLiquido, 0);
                $pagamento['status']=$captura->getStatus();
            }

        } catch (CieloRequestException $e) {
            $pagamento['status'] = 0;        
        }finally{
            return $pagamento;
        }

    }

    // public static function sincronizaTransacaoCielo(string $id_transacao){
    //     $consulta = "https://apiquerysandbox.cieloecommerce.cielo.com.br/1/sales/". $id_transacao;
    //     $id = $_ENV['DADOS_PAGAMENTO_MERCHANTID'];
    //     $key = $_ENV['DADOS_PAGAMENTO_MERCHANTKEY'];
    //     $curl = curl_init();

    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => $consulta,
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 0,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'GET',
    //         CURLOPT_HTTPHEADER => array(
    //             'Content-Type: application/json',
    //             'MerchantKey:'.$key,
    //             'merchantId:'. $id
    //         ),
    //     ));

    //     $response = curl_exec($curl);

    //     curl_close($curl);
    //     return $response;
    // }
}
