<?php

namespace MobileStock\model\PagamentoIugu;

use Exception;
use MobileStock\helper\IuguEstaIndisponivel;
use MobileStock\helper\LoggerFactory;
use Monolog\Logger;

/**
 * @deprecated
 * Usar @\MobileStock\service\Iugu\IuguHttpClient
 */
class PagamentosIugu implements \JsonSerializable
{
    protected $apiToken;
	protected $contaMobile;
    protected $NomeFaturamento;
    protected $transacao;
    protected $jsonEnvio;
    protected $method;
    protected $url;
    protected $idPagador;
    protected $idSellerConta;
    protected $respsotaIugo = [];
    protected $complementoUrl = '';


    public function __construct()
    {
        $this->apiToken = $_ENV['DADOS_PAGAMENTO_IUGUAPITOKEN'];
        $this->contaMobile = $_ENV['DADOS_PAGAMENTO_IUGUCONTAMOBILE'];
        $this->NomeFaturamento = 'Mobile Stock';
    }

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;            
        }else{
            $this->$atrib = null;
        }
    }

    public function __get($atrib) 
    {
        return $this->$atrib;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    protected function requestIugu()
    {
        $headers = [
            'Accept: application/json'
        ];

        $link = $this->url . "?api_token=" . $this->apiToken . $this->complementoUrl;

        $curl = curl_init($link);

        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

        switch ($this->method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
        }

        if ($this->jsonEnvio !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->jsonEnvio);

            $headers[] = 'Content-Type: application/json';
        } else {
            $headers[] = 'Content-Length: 0';
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $response   = curl_exec($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

       
        if (curl_errno($curl)) {
            $message = sprintf('cURL error[%s]: %s', curl_errno($curl), curl_error($curl));

            throw new \RuntimeException($message);
        }

        curl_close($curl);

        if (stripos($link, 'payment_token') === false) {
            $nivelLog = $statusCode === 200 ? Logger::INFO : Logger::ERROR;

            $logs = LoggerFactory::arquivo('logs_requisicao_iugu.log');
            $logs->addRecord($nivelLog, "$this->method | $link Corpo: $this->jsonEnvio Resposta: $response");
        }


        return $this->respsotaIugo = ["codigo"=>$statusCode, "resposta"=>json_decode($response)];
    }

    protected function retornoErro(){
        if (in_array($this->respsotaIugo['codigo'], array(520, 502))) {
            throw new IuguEstaIndisponivel();
        }

        if(in_array($this->respsotaIugo['codigo'],array(400,401,404)) && is_string($this->respsotaIugo['resposta']->errors)){
            throw new Exception($this->respsotaIugo['resposta']->errors, 1); 
        }

        $mensagemErroIugu = '';
        foreach (get_object_vars($this->respsotaIugo['resposta']->errors) as $key => $value) {
            $key = str_replace('payer.address.zip_code', 'CEP', $key);
            $mensagemErroIugu .= $key.' '.implode('-',(array) $value).' ';
        }

        if ($mensagemErroIugu) {
            throw new Exception($mensagemErroIugu);
        }

        $mensagemErroIugu = $this->respsotaIugo['resposta']->errors;

        throw new Exception(json_encode(get_object_vars($this->respsotaIugo['resposta'])) ?? 'Erro desconhecido Iugu', 1);
    }
}