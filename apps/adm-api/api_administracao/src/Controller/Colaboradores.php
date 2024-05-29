<?php

namespace api_administracao\Controller;

use PDO;
use api_administracao\Models\Request_m;
use api_administracao\Models\Conect;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\HttpClient;
use MobileStock\helper\RegrasAutenticacao;
use MobileStock\helper\ValidacaoException;
use MobileStock\helper\Validador;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ConfiguracaoService;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class Colaboradores extends Request_m
{
    public function __construct()
    {
        parent::__construct();
        $this->conexao = Conect::conexao();
    }

    public function buscaNovosClientes()
    {
        try {
            $this->retorno['data'] = ColaboradoresService::buscaClientesNovos($this->conexao);
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Operação realizada com sucesso!';
            $this->status = 200;
        } catch (Exception $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    public function geraLinkLogado()
    {
        try {
            $this->retorno['data']['link'] = RegrasAutenticacao::geraTokenTemporario($this->conexao, $this->idUsuario);
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Operação realizada com sucesso!';
            $this->status = 200;
        } catch (Exception $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    public function buscaDiasTransferenciaColaboradores()
    {
        $datas = ConfiguracaoService::buscaDiasTransferenciaColaboradores();
        return $datas;
    }

    public function atualizarDiasTransferenciaColaboradores()
    {
        DB::beginTransaction();

        if (Auth::id() !== 356) {
            throw new UnprocessableEntityHttpException(
                'Você não tem autorização para alterar os dias de pagamento dos fornecedores!'
            );
        }

        $dados = FacadesRequest::all();

        Validador::validar($dados, [
            'dias_pagamento_transferencia_fornecedor_MELHOR_FABRICANTE' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'dias_pagamento_transferencia_fornecedor_EXCELENTE' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'dias_pagamento_transferencia_fornecedor_REGULAR' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'dias_pagamento_transferencia_fornecedor_RUIM' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'dias_pagamento_transferencia_CLIENTE' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'dias_pagamento_transferencia_ENTREGADOR' => [Validador::NAO_NULO, Validador::NUMERO],
            'dias_pagamento_transferencia_antecipacao' => [Validador::NAO_NULO, Validador::NUMERO],
        ]);

        ConfiguracaoService::atualizarDiasTransferenciaColaboradores($dados);

        DB::commit();
    }

    public function buscaColaboradoresFiltros()
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'filtro' => [Validador::OBRIGATORIO],
            'nivel_acesso' => [Validador::SE(Validador::OBRIGATORIO, Validador::NUMERO)],
        ]);
        try {
            Validador::validar($dadosJson, [
                'filtro' => [Validador::STRING],
            ]);
        } catch (ValidacaoException $ve) {
            $dadosJson['filtro'] = preg_replace('/[^0-9]/', '', $dadosJson['filtro']);
        }

        $colaboradores = ColaboradoresService::buscaColaboradoresComFiltros(
            $dadosJson['filtro'],
            $dadosJson['nivel_acesso'] ?? null
        );

        return $colaboradores;
    }

    public function mudaTipoEmbalagem()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $jsonData = json_decode($this->json, true);

            Validador::validar($jsonData, [
                'id_colaborador_destinatario' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'tipo_embalagem' => [Validador::OBRIGATORIO, Validador::ENUM('SA', 'CA')],
            ]);

            ColaboradoresService::mudaTipoEmbalagem(
                $this->conexao,
                $jsonData['id_colaborador_destinatario'],
                $jsonData['tipo_embalagem']
            );
            $this->codigoRetorno = 200;
            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno['message'] = $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaLojaMed(array $dadosJson)
    {
        try {
            $http = new HttpClient();
            $rota = $_ENV['URL_MED_API'];
            $token = $_ENV['MED_AUTH_TOKEN'];
            $http->post("{$rota}admin/link/{$dadosJson['id_colaborador']}", null, ["Authorization: Bearer $token"]);

            $this->resposta = $http->body;
            $this->codigoRetorno = $http->codigoRetorno;
        } catch (\Throwable $e) {
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function criarLojaMed()
    {
        try {
            $http = new HttpClient();
            $rota = $_ENV['URL_MED_API'];
            $token = $_ENV['MED_AUTH_TOKEN'];
            $dadosJson = json_decode($this->json, true);
            $http->post("{$rota}admin/cadastrar_loja", $dadosJson, ["Authorization: Bearer $token"]);

            $this->resposta = $http->body;
            $this->codigoRetorno = $http->codigoRetorno;
            ColaboradoresRepository::adicionaPermissaoUsuario($this->conexao, $dadosJson['id_usuario'], [13]);
        } catch (\Throwable $e) {
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function salvarObservacaoColaborador(PDO $conexao, Request $request)
    {
        try {
            $conexao->beginTransaction();

            $dados = $request->all();

            Validador::validar($dados, [
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'observacao' => [Validador::SE(Validador::OBRIGATORIO, [Validador::TAMANHO_MAXIMO(1000)])],
            ]);

            ColaboradoresService::salvarObservacaoColaborador($conexao, $dados['id_colaborador'], $dados['observacao']);

            $conexao->commit();
        } catch (\Throwable $ex) {
            $conexao->rollBack();
            throw $ex;
        }
    }
}
