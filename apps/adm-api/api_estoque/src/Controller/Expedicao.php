<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\database\Conexao;
use MobileStock\helper\Images\Etiquetas\ImagemEtiquetaExpedicao;
use MobileStock\helper\Validador;
use MobileStock\jobs\ImagemRetiradaMs;
use MobileStock\jobs\NotificarChegadaProdutosPontoParado;
use MobileStock\model\Entrega;
use MobileStock\model\EntregasEtiqueta;
use MobileStock\model\EntregasFaturamentoItem;
use MobileStock\model\LogisticaItemModel;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\EntregaService\EntregasFaturamentoItemService;
use MobileStock\service\EntregaService\EntregasFechadasTempService;
use MobileStock\service\LogisticaItemService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasProdutosTrocasService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class Expedicao extends Request_m
{
    private $conexao;
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }
    public function buscaStatusEntregas()
    {
        $retorno = EntregaServices::consultaStatusDeEntrega();
        return $retorno;
    }
    public function buscaEntregasVolumesDoColaborador(int $idColaborador)
    {
        $retorno = EntregaServices::buscaEntregasVolumesDoColaborador($idColaborador);
        return $retorno;
    }
    public function consultaEntregaId(int $idEntrega)
    {
        $retorno = EntregaServices::ConsultaEntregaCliente($idEntrega);
        return $retorno;
    }

    public function criaEntregaOuMesclaComEntregaExistente()
    {
        $dadosJson = Request::all();

        Validador::validar($dadosJson, [
            'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'tipo_frete' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'volumes' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_raio' => [Validador::SE(Validador::OBRIGATORIO, Validador::NUMERO)],
            'produtos' => [Validador::SE(Validador::OBRIGATORIO, Validador::ARRAY)],
        ]);

        DB::getLock($dadosJson['id_cliente'], $dadosJson['tipo_frete'], $dadosJson['id_raio']);
        DB::beginTransaction();
        $idDeEntregaExistente = EntregaServices::criaEntregaOuMesclaComEntregaExistente(
            $dadosJson['id_cliente'],
            $dadosJson['tipo_frete'],
            $dadosJson['volumes'],
            $dadosJson['id_raio'] ?? null,
            $dadosJson['produtos'] ?? []
        );

        DB::commit();

        return new Response(null, $idDeEntregaExistente ? Response::HTTP_NO_CONTENT : Response::HTTP_CREATED);
    }

    public function recalcularEtiquetas()
    {
        DB::beginTransaction();

        $dadosJson = Request::all();

        Validador::validar($dadosJson, [
            'id_entrega' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'volumes' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $entregasEtiquetas = new EntregaServices();
        $entregasEtiquetas->recalculaEtiquetas($dadosJson['id_entrega'], $dadosJson['volumes']);

        DB::commit();
    }

    public function confirmaBipagemDeVolumes(int $idEntrega)
    {
        DB::beginTransaction();

        $dados = Request::all();
        Validador::validar($dados, [
            'etiquetas' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        EntregaServices::validaEtiqueta($idEntrega, $dados['etiquetas']);

        $entrega = Entrega::configuraNovaSituacao($idEntrega, 'BIPAGEM_PADRAO');

        DB::commit();

        if ($entrega->id_tipo_frete === 3) {
            dispatch(new ImagemRetiradaMs($entrega->id));
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
    /**
     * @issue Obsolescência programada: https://github.com/mobilestock/backend/issues/125
     */
    public function descobreCliente(ColaboradoresService $colaboradoresService, string $idClienteOuUuid)
    {
        switch (true) {
            case preg_match(Entrega::REGEX_ETIQUETA_CLIENTE_LEGADO, $idClienteOuUuid) ||
                preg_match(Entrega::REGEX_ETIQUETA_CLIENTE, $idClienteOuUuid):
                if (preg_match(Entrega::REGEX_ETIQUETA_CLIENTE_LEGADO, $idClienteOuUuid)) {
                    $idCliente = explode('_', $idClienteOuUuid)[1];
                } else {
                    $idCliente = (int) mb_substr($idClienteOuUuid, 1);
                }

                $tipoDeEtiqueta = 'ETIQUETA_CLIENTE';
                break;
            case preg_match(EntregasEtiqueta::REGEX_VOLUME_LEGADO, $idClienteOuUuid) ||
                preg_match(EntregasEtiqueta::REGEX_VOLUME, $idClienteOuUuid):
                if (preg_match(EntregasEtiqueta::REGEX_VOLUME_LEGADO, $idClienteOuUuid)) {
                    $idCliente = EntregaServices::buscaIdClienteDaEntrega(explode('_', $idClienteOuUuid)[0]);
                } else {
                    $idCliente = (int) explode('_', $idClienteOuUuid)[0];
                }
                $tipoDeEtiqueta = 'ETIQUETA_VOLUME';
                break;
            case preg_match(LogisticaItemModel::REGEX_ETIQUETA_PRODUTO, $idClienteOuUuid):
                $idCliente = (int) explode('_', $idClienteOuUuid)[0];
                $tipoDeEtiqueta = 'ETIQUETA_PRODUTO';
                break;

            default:
                throw new UnprocessableEntityHttpException('Bipe o código qr de produto ou volume ou cliente.');
        }
        if (Gate::allows('ADMIN')) {
            $colaboradoresService->id = $idCliente;
            $colaboradoresService->buscaSituacaoFraude(DB::getPdo(), ['DEVOLUCAO']);
            if (in_array($colaboradoresService->situacao_fraude, ['PE', 'FR'])) {
                throw new UnprocessableEntityHttpException(
                    'A liberação da expedição não é possível, pois o cliente está sendo identificado como suspeito de fraude.'
                );
            }
        }
        TransacaoFinanceirasProdutosTrocasService::converteDebitoPendenteParaNormalSeNecessario($idCliente);
        TransacaoFinanceirasProdutosTrocasService::sincronizaTrocaPendenteAgendamentoSeNecessario($idCliente);
        $retorno = EntregaServices::consultaSituacaoParaEntregar($idCliente, $tipoDeEtiqueta);

        return $retorno;
    }

    public function itensInseridosNaEntrega()
    {
        $entregasFI = new EntregasFaturamentoItemService();
        try {
            $pesquisa = (string) $this->request->get('pesquisa', '');
            $this->retorno['data'] = $entregasFI->listaItensInseridosNaEntrega($this->conexao, $pesquisa);
        } catch (\InvalidArgumentException $exception) {
            $this->codigoRetorno = $exception->getCode();
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Erro ao buscar entrega';
        } catch (\PDOException $exception) {
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Erro ao buscar entregas';
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno = ['message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function confirmaChegadaDeProdutos()
    {
        DB::beginTransaction();
        $dados = Request::all();
        Validador::validar($dados, [
            'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);
        $idsDeEntrega = EntregasFaturamentoItemService::buscaIdsDeEntregaEmTransporte($dados['produtos']);
        foreach ($idsDeEntrega as $idDeEntrega) {
            Entrega::configuraNovaSituacao($idDeEntrega, 'BIPAGEM_PADRAO');
        }

        EntregasFaturamentoItem::confirmaConferencia($dados['produtos']);

        DB::commit();

        if (Gate::allows('PONTO_RETIRADA')) {
            dispatch(new NotificarChegadaProdutosPontoParado($dados['produtos']));
        }
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
    public function buscaProdutosDisponiveisParaEntregarAoCliente(int $idColaborador)
    {
        $resposta = EntregasFaturamentoItemService::listaProdutosDisponiveisParaEntregarAoCliente($idColaborador);

        return $resposta;
    }
    public function ListaEntregaFaturamentoItem()
    {
        $dados = EntregasFaturamentoItemService::listaEntregasFaturamentoItem();
        return $dados;
    }

    public function confirmaEntregaDeProdutosAoCliente(EntregasFaturamentoItem $entregasItem)
    {
        DB::beginTransaction();
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        $entregasItem->confirmaEntregaDeProdutos($dadosJson['produtos'], $dadosJson['recebedor'] ?? null);

        if (Gate::any(['PONTO_RETIRADA', 'ENTREGADOR'])) {
            EntregasFaturamentoItemService::buscaProdutosParaNotificarEntregaPontoParado($dadosJson['produtos']);
        }

        DB::commit();
    }

    public function ListaEtiquetasJsonPorEntrega(int $idEntrega, int $volume)
    {
        $dados = Request::all();

        $imprimirZpl = !!$dados['zpl'];
        $uuid = !empty($dados['uuid']) ? $dados['uuid'] : null;
        $listaDeEtiquetas = EntregaServices::ConsultaEtiquetas($idEntrega);
        $listaFiltrada =
            array_values(
                array_filter($listaDeEtiquetas, function ($item) use ($volume, $uuid) {
                    if (mb_strlen($uuid)) {
                        return $uuid === explode('_', $item['qrcode_entrega'])[1];
                    } else {
                        return $volume < $item['volume'];
                    }
                })
            ) ?:
            $listaDeEtiquetas;

        if ($imprimirZpl) {
            $listaFiltradaZpl = array_map(function ($item) {
                $imagem = new ImagemEtiquetaExpedicao(
                    $item['id_entrega'],
                    $item['cidade'],
                    $item['volume'],
                    $item['qrcode_entrega'],
                    !empty($item['ponto_movel'])
                        ? $item['nome_remetente']
                        : "{$item['nome_cliente']} - {$item['nome_remetente']}",
                    $item['apelido_raio']
                );
                $item = $imagem->criarZpl();
                return $item;
            }, $listaFiltrada);

            return $listaFiltradaZpl;
        } else {
            return $listaFiltrada;
        }
    }

    public function buscarDadosEtiquetaEnvio(string $etiquetaExpedicao)
    {
        $request = Request::all();

        Validador::validar($request, [
            'acao' => [Validador::ENUM('VISUALIZAR', 'IMPRIMIR')],
        ]);

        $resultado = EntregaServices::buscarDadosEtiquetaEnvio($etiquetaExpedicao, $request['acao']);
        return $resultado;
    }

    public function verificaLogisticaPendente(string $identificador)
    {
        $resultado = LogisticaItemService::listaLogisticaPendenteParaEnvio($identificador);
        if (empty($resultado)) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        } else {
            $colaboradoresService = new ColaboradoresService();
            $colaboradoresService->id = $resultado['id_remetente'];
            $colaboradoresService->buscaSituacaoFraude(DB::getPdo(), ['DEVOLUCAO']);
            if (in_array($colaboradoresService->situacao_fraude, ['PE', 'FR'])) {
                throw new Exception(
                    'A liberação da expedição não é possível, pois o cliente está sendo identificado como suspeito de fraude.'
                );
            }
            return $resultado;
        }
    }
    public function encerrarEntrega(int $idEntrega)
    {
        DB::beginTransaction();
        Entrega::configuraNovaSituacao($idEntrega, 'FECHAR_ENTREGA');
        EntregasFechadasTempService::adicionaEntregaFechadaTemp($idEntrega);
        DB::commit();
    }

    public function buscaVolumesDaEntrega(int $idEntrega)
    {
        $listaDeEtiquetas = EntregaServices::ConsultaEtiquetas($idEntrega);

        return $listaDeEtiquetas;
    }

    public function buscarEntregasFechadasTemp()
    {
        $entregaManipulada = FacadesRequest::all();

        Validador::validar($entregaManipulada, [
            'manipulada' => [Validador::ENUM('NAO_MANIPULADA', 'MANIPULADA')],
        ]);

        $retorno = EntregasFechadasTempService::buscarEntregasFechadasTemp($entregaManipulada['manipulada']);

        $entregas = [];

        foreach ($retorno as $value) {
            $entregas[$value['nome_grupo']][] = $value;
        }

        krsort($entregas);

        $entregas = array_map(function ($entrega) {
            usort($entrega, function ($a, $b) {
                return $a['entrega_manipulada'] <=> $b['entrega_manipulada'];
            });
            return $entrega;
        }, $entregas);

        return $entregas;
    }

    public function manipularEntregaFechada()
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
                'id_entrega' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            EntregasFechadasTempService::manipularEntregaFechada(
                $this->conexao,
                $jsonData['id_entrega'],
                $this->idUsuario
            );

            $this->retorno['status'] = true;
            $this->retorno['data'] = '';
            $this->retorno['message'] = 'A entrega foi manipulada com sucesso';
            $this->conexao->commit();
        } catch (\Throwable $ex) {
            $this->conexao->rollBack();
            $this->codigoRetorno = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
}
