<?php

namespace MobileStock\model;

header('Content-Type: application/xml; charset=unicode');

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use PDO;
use MobileStock\repository\ColaboradoresRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PacReverso
{
    protected PDO $conexao;
    private $URL = 'https://cws.correios.com.br/logisticaReversaWS/logisticaReversaService/logisticaReversaWS?wsdl';
    // private $CORREIOS = "https://cws.correios.com.br/logisticaReversaWS/logisticaReversaService/logisticaReversaWS";
    private $id_cliente;
    private $id_transacao;
    private float $valor_devolucao;

    public function __construct(PDO $conexao, int $id_cliente, int $id_transacao, float $valor_devolucao)
    {
        $this->conexao = $conexao;
        $this->id_cliente = $id_cliente;
        $this->id_transacao = $id_transacao;
        $this->valor_devolucao = $valor_devolucao;
    }

    public function solicitarPac()
    {
        $informacoesCliente = $this->buscaDadosClienteParaPac();

        $objetoContextoErro = [
            'id_cliente' => $this->id_cliente,
            'id_transacao' => $this->id_transacao,
            'valor_devolucao' => $this->valor_devolucao,
        ];

        if (empty($informacoesCliente)) {
            app(Logger::class)->withContext($objetoContextoErro);
            throw new NotFoundHttpException('Não foi possível encontrar as informações do cliente.');
        }
        $numero = rand(1, 999);
        $informacoesCentral = ColaboradoresRepository::buscaColaboradorPorID(12);

        $transacaoPac = $this->id_transacao . 0000 . rand(100, 999);
        $objetoContextoErro['informacoes_cliente'] = $informacoesCliente;
        $objetoContextoErro['id_transacao_pac'] = $transacaoPac;
        app(Logger::class)->withContext($objetoContextoErro);

        $xml =
            '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
            '<Body>' .
            '<solicitarPostagemReversa xmlns="http://service.logisticareversa.correios.com.br/">' .
            '<codAdministrativo xmlns="">16032543</codAdministrativo>' .
            '<codigo_servico xmlns="">03301</codigo_servico>' .
            '<cartao xmlns="">0071946241</cartao>' .
            '<!-- Optional -->' .
            '<destinatario xmlns="">' .
            '<nome>AQUARIUS COMERCIO DE CALCADOS LTDA ME</nome>' .
            '<logradouro>Rua Pará de Minas</logradouro>' .
            '<numero>150</numero>' .
            '<complemento>galpao fundo</complemento>' .
            '<bairro>Centro</bairro>' .
            '<cidade>Nova Serrana</cidade>' .
            '<uf>MG</uf>' .
            '<cep>35520090</cep>' .
            "<telefone>{$informacoesCentral['telefone']}</telefone>" .
            "<email>{$informacoesCentral['email']}</email>" .
            '</destinatario>' .
            '<!-- Optional -->' .
            '<coletas_solicitadas xmlns="">' .
            '<tipo>A</tipo>' .
            '<ag>30</ag>' .
            "<id_cliente>{$informacoesCliente['id']}</id_cliente>" .
            '<valor_declarado>' .
            $this->valor_devolucao .
            '</valor_declarado>' .
            '<cklist></cklist>' .
            '<documento></documento>' .
            '<!-- Optional -->' .
            '<remetente>' .
            "<nome>{$informacoesCliente['razao_social']}</nome>" .
            "<logradouro>{$informacoesCliente['logradouro']}</logradouro>" .
            "<numero>$numero</numero>" .
            '<complemento></complemento>' .
            "<bairro>{$informacoesCliente['bairro']}</bairro>" .
            '<referencia></referencia>' .
            "<cidade>{$informacoesCliente['cidade']}</cidade>" .
            "<uf>{$informacoesCliente['uf']}</uf>" .
            "<cep>{$informacoesCliente['cep']}</cep>" .
            '</remetente>' .
            '<obj_col>' .
            '<item>1</item>' .
            '<desc>Referente ao pedido ' .
            $transacaoPac .
            '</desc>' .
            '<entrega></entrega>' .
            '<num></num>' .
            "<id>{$informacoesCliente['id']}</id>" .
            '</obj_col>' .
            '</coletas_solicitadas>' .
            '</solicitarPostagemReversa>' .
            '</Body>' .
            '</Envelope>';

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml; charset=utf-8',
                'Authorization: Basic MjUyNTIzMzkwMDAxMDY6YXJ0MzA5MTIzMDgyNjAx',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    public function buscaDadosClienteParaPac(): array
    {
        $join = 'INNER JOIN colaboradores_enderecos ON
                colaboradores_enderecos.id_colaborador = colaboradores.id AND
                colaboradores_enderecos.eh_endereco_padrao = 1';
        $idCidade = 'colaboradores_enderecos.id_cidade';
        $binds = [
            'id_colaborador' => $this->id_cliente,
        ];

        if ($this->id_transacao > 0) {
            $join .= " INNER JOIN transacao_financeiras_metadados ON
                transacao_financeiras_metadados.id_transacao = :id_transacao
                AND transacao_financeiras_metadados.chave = 'ENDERECO_CLIENTE_JSON'";
            $idCidade = "COALESCE(
                    JSON_VALUE(transacao_financeiras_metadados.valor, '$.id_cidade'),
                    colaboradores_enderecos.id_cidade
                )";
            $binds['id_transacao'] = $this->id_transacao;
        }

        $informacoes = DB::selectOne(
            "SELECT
                colaboradores.id,
                colaboradores.razao_social,
                municipios.nome AS `cidade`,
                municipios.uf,
                municipios.logradouro,
                municipios.bairro,
                municipios.cep
            FROM colaboradores
            $join
            INNER JOIN municipios ON municipios.id = $idCidade
            WHERE colaboradores.id = :id_colaborador",
            $binds
        );

        return $informacoes;
    }
    // public function acompanharPedido($numero_pac)
    // {
    //     $xml = '<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">' .
    //         '<Body>' .
    //         '<acompanharPedido xmlns="http://service.logisticareversa.correios.com.br/">' .
    //         '<codAdministrativo xmlns="">16032543</codAdministrativo>' .
    //         '<tipoBusca xmlns="">U</tipoBusca>' .
    //         '<tipoSolicitacao xmlns="">A</tipoSolicitacao>' .
    //         '<numeroPedido xmlns="">' . $numero_pac . '</numeroPedido>' .
    //         '</acompanharPedido>' .
    //         '</Body>' .
    //         '</Envelope>';

    //     $curl = curl_init();

    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => $this->CORREIOS,
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => "",
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 0,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => "POST",
    //         CURLOPT_POSTFIELDS => $xml,
    //         CURLOPT_HTTPHEADER => array(
    //             "Content-Type: text/xml; charset=utf-8",
    //             "Authorization: Basic MjUyNTIzMzkwMDAxMDY6YXJ0MzA5MTIzMDgyNjAx"
    //         ),
    //     ));

    //     $response = curl_exec($curl);

    //     curl_close($curl);
    //     return $response;
    // }
}
