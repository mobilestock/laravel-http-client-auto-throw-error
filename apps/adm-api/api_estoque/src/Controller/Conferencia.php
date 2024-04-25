<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use MobileStock\database\Conexao;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\model\GeolocalizacaoBipagem;
use MobileStock\service\Conferencia\ConferenciaItemService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\GeolocalizacaoBipagemService;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class Conferencia extends Request_m
{
    private $conexao;
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        $this->conexao = Conexao::criarConexao();
        parent::__construct();
    }
    public function confereItem(Request $request, Gate $gate, PDO $conexao, Authenticatable $usuario)
    {
        try {
            $dadosJson = $request->all();
            $conexao->beginTransaction();
            Validador::validar($dadosJson, [
                'lista_de_uuid_produto' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            if ($gate->allows('FORNECEDOR')) {
                Validador::validar($dadosJson, [
                    'latitude' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'longitude' => [Validador::OBRIGATORIO, Validador::NUMERO],
                ]);

                $coordenadas = ConfiguracaoService::buscaCoordenadasCentral($conexao);
                $distancia = Globals::Haversine(
                    $dadosJson['latitude'],
                    $dadosJson['longitude'],
                    $coordenadas['latitude_central'],
                    $coordenadas['longitude_central']
                );
                if ($distancia > 0.05) {
                    throw new NotAcceptableHttpException(
                        'Nenhuma central foi encontrada, favor ficar no máximo à 50 metros da central para fazer a conferência.'
                    );
                }

                $localizacaBipagem = new GeolocalizacaoBipagem();
                $localizacaBipagem->id_usuario = $usuario->id;
                $localizacaBipagem->latitude = $dadosJson['latitude'];
                $localizacaBipagem->longitude = $dadosJson['longitude'];
                $localizacaBipagem->motivo = 'Conferindo ' . implode(',', $dadosJson['lista_de_uuid_produto']);

                GeolocalizacaoBipagemService::salvaRegistro($conexao, $localizacaBipagem);
            }

            ConferenciaItemService::confere($conexao, $dadosJson['lista_de_uuid_produto'], $usuario->id);
            $conexao->commit();
            dispatch(new GerenciarAcompanhamento($dadosJson['lista_de_uuid_produto']));
        } catch (\Throwable $th) {
            $conexao->rollBack();
            throw $th;
        }
    }

    public function buscaItensEntreguesCentral()
    {
        try {
            $this->retorno['data'] = ConferenciaItemService::buscaConferidosDoSeller(
                $this->conexao,
                $this->idColaborador
            );
            $this->retorno['message'] = 'Produtos consultados!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage() ?: 'Falha ao buscar produtos.';
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function itensDisponiveisParaAdicionarNaEntrega()
    {
        try {
            $pesquisa = (string) $this->request->get('pesquisa', '');
            $this->retorno['data'] = ConferenciaItemService::listaItensDisponiveisParaAdicionarNaEntrega(
                $this->conexao,
                $this->categoriaDoUsuario === 'ADM' ? 1 : $this->idColaborador,
                $pesquisa
            );

            $this->retorno['message'] = 'Pares encontrados com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage() ?: 'Erro ao buscar itens para conferência';
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function descobrirItemParaEntrarNaConferencia(string $uuidProduto)
    {
        $lista = ConferenciaItemService::buscaDetalhesDoItem($uuidProduto);

        return $lista;
    }
    public function listaItemsAConferir()
    {
        try {
            $lista = ConferenciaItemService::listaItemsParaConferir($this->conexao, $this->idColaborador);

            $this->codigoRetorno = 200;
            $this->retorno['data'] = $lista;
            $this->retorno['status'] = true;
        } catch (\Throwable $th) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['data'] = null;
            $this->retorno['message'] = $th->getMessage() ?: 'Falha ao buscar items';
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}
