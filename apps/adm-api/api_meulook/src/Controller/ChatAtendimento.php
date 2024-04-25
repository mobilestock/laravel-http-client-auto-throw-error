<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Illuminate\Http\Request;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\ColaboradoresService;
use PDO;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ChatAtendimento extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '0';
        parent::__construct();
    }

    // public function buscaAlerta()
    // {
    //     try {
    //         $mensagemAlerta = ConfiguracaoService::buscaAlertaChatAtendimento($this->conexao);
    //         $this->retorno['data']['mensagem_alerta'] = $mensagemAlerta;
    //         $this->codigoRetorno = 200;
    //         $this->retorno['status'] = true;
    //         $this->retorno['message'] = 'Alertas verificados com sucesso!';
    //     } catch (\Throwable $error) {
    //         $this->codigoRetorno = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $error->getMessage();
    //     } finally {
    //         $this->respostaJson
    //             ->setData($this->retorno)
    //             ->setStatusCode($this->codigoRetorno)
    //             ->send();
    //     }
    // }

    public function buscaPrevisao(Request $request, PDO $conexao)
    {
        $dadosJson['telefone'] = $request->telefone();

        $colaboradoresIDs = ColaboradoresService::buscaIdColaboradoresPorTelefone($conexao, $dadosJson['telefone']);

        if (empty($colaboradoresIDs)) {
            throw new BadRequestHttpException(
                "pois não há nenhum cadastro com este telefone. ({$dadosJson['telefone']})"
            );
        }

        $previsoes = ProdutosRepository::consultaPrevisoesDeColaboradores($conexao, $colaboradoresIDs);

        if (empty($previsoes) && empty($colaboradoresIDs)) {
            throw new BadRequestHttpException(
                "pois não existe nenhum cadastro com este número` ({$dadosJson['telefone']})."
            );
        }

        return $previsoes;
    }
    public function dadosWhatsAppAtendimento()
    {
        $dadosJson['telefone'] = \Illuminate\Support\Facades\Request::telefone();

        $dadosClientes = ColaboradoresService::buscaDadosClienteWhatsAppSuporte($dadosJson['telefone']);

        if (empty($dadosClientes)) {
            return new Response('Cadastro não encontrado.', Response::HTTP_NO_CONTENT);
        }

        return $dadosClientes;
    }
}
