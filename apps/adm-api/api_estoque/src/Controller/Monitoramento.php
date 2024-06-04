<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use MobileStock\database\Conexao;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\model\Entrega;
use MobileStock\service\EntregaService\EntregasDevolucoesItemServices;
use MobileStock\service\MessageService;
use MobileStock\service\Monitoramento\MonitoramentoService;
use MobileStock\service\TipoFreteService;
use Symfony\Component\HttpFoundation\Response;

class Monitoramento extends Request_m
{
    private $conexao;

    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    public function buscaProdutosQuantidade()
    {
        $resultado = MonitoramentoService::buscaProdutosQuantidade();

        return $resultado;
    }

    public function buscaProdutosChegada()
    {
        $resultado = MonitoramentoService::buscaProdutosChegada();

        return $resultado;
    }

    public function buscaProdutosEntrega()
    {
        $resultado = MonitoramentoService::buscaProdutosEntrega(Auth::user()->id_colaborador);
        return $resultado;
    }

    public function enviarMensagemWhatsApp(MessageService $mensageiro)
    {
        $dadosJson = Request::all();
        $dadosJson['telefone'] = Request::telefone();

        Validador::validar($dadosJson, [
            'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'razao_social' => [Validador::OBRIGATORIO, Validador::STRING],
            'foto' => [Validador::OBRIGATORIO],
        ]);

        $dadosDoPonto = TipoFreteService::buscaDadosPontoComIdColaborador();

        $mensagem = "Olá, {$dadosJson['razao_social']}!" . PHP_EOL . PHP_EOL;
        $mensagem .= 'O produto acima já pode ser retirado.' . PHP_EOL . PHP_EOL;
        $mensagem .=
            "Mostre o QRCode acima para o ponto de retirada *{$dadosDoPonto['nome']}* no endereço *{$dadosDoPonto['mensagem']}* durante o horário de funcionamento *{$dadosDoPonto['horario_de_funcionamento']}*." .
            PHP_EOL .
            PHP_EOL;
        $mensagem .=
            "Localização do ponto de retirada: https://www.google.com/maps/search/{$dadosDoPonto['latitude']},{$dadosDoPonto['longitude']}" .
            PHP_EOL .
            PHP_EOL;
        $mensagem .= "Se houver dúvidas sobre a entrega, entre em contato com o ponto de retirada: https://wa.me/55{$dadosDoPonto['telefone']}";

        $mensageiro->sendImageWhatsApp($dadosJson['telefone'], $dadosJson['foto']);
        $mensageiro->sendImageWhatsApp(
            $dadosJson['telefone'],
            Globals::geraQRCODE(Entrega::formataEtiquetaCliente($dadosJson['id_cliente']))
        );
        $mensageiro->sendMessageWhatsApp($dadosJson['telefone'], $mensagem);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    public function buscaTrocasPendentes()
    {
        $resultado = EntregasDevolucoesItemServices::buscaTrocasPendentes();
        return $resultado;
    }
}
