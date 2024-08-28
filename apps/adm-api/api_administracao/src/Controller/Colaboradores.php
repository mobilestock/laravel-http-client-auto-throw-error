<?php

namespace api_administracao\Controller;

use api_administracao\Models\Conect;
use api_administracao\Models\Request_m;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\HttpClient;
use MobileStock\helper\RegrasAutenticacao;
use MobileStock\helper\ValidacaoException;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorModel;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\ColaboradoresService;
use PDO;

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

    /**
     * @issue: https://github.com/mobilestock/backend/issues/528
     */
    public function criarLojaMed()
    {
        $rota = env('URL_MED_API');
        $token = env('MED_AUTH_TOKEN');

        $dadosJson = FacadesRequest::all();
        $colaborador = ColaboradorModel::buscaInformacoesColaborador($dadosJson['id_revendedor']);

        Http::withHeaders(['Authorization' => "Bearer $token"])
            ->post("{$rota}admin/cadastrar_loja", array_merge($dadosJson, ['telefone' => $colaborador->telefone]))
            ->throw();

        ColaboradoresRepository::adicionaPermissaoUsuario(DB::getPdo(), $dadosJson['id_usuario'], [13]);
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
