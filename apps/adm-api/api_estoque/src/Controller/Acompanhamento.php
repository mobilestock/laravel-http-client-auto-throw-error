<?php

namespace api_estoque\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\jobs\GerenciarAcompanhamento;
use MobileStock\model\AcompanhamentoTemp;
use MobileStock\model\LogisticaItemModel;
use MobileStock\service\AcompanhamentoTempService;
use MobileStock\service\LogisticaItemService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Acompanhamento
{
    public function adicionarAcompanhamentoDestino()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'id_destinatario' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_tipo_frete' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_cidade' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_raio' => [Validador::SE(Validador::OBRIGATORIO, VALIDADOR::NUMERO)],
        ]);

        $uuidProdutos = LogisticaItemModel::buscaProdutosParaAdicionarNoAcompanhamento(
            $dados['id_destinatario'],
            $dados['id_tipo_frete'],
            $dados['id_cidade'],
            $dados['id_raio'] ?? null
        );
        if (empty($uuidProdutos)) {
            throw new BadRequestHttpException('Não há produtos para acompanhar');
        }

        dispatch(new GerenciarAcompanhamento($uuidProdutos, GerenciarAcompanhamento::CRIAR_ACOMPANHAMENTO));
    }

    public function adicionarAcompanhamentoDestinoGrupo()
    {
        $dados = Request::all();

        Validador::validar(['dados' => $dados], ['dados' => [Validador::OBRIGATORIO, Validador::ARRAY]]);

        $uuidProdutos = [];
        foreach ($dados as $item) {
            $uuidProdutos[] = LogisticaItemModel::buscaProdutosParaAdicionarNoAcompanhamento(
                $item['id_colaborador'],
                $item['id_tipo_frete'],
                $item['id_cidade'],
                $item['id_raio'] ?? null
            );
        }

        $uuidProdutos = array_merge(...$uuidProdutos);

        if (empty($uuidProdutos)) {
            throw new BadRequestHttpException('Não há destinos que possam ser acompanhados neste grupo');
        }

        dispatch(new GerenciarAcompanhamento($uuidProdutos, GerenciarAcompanhamento::CRIAR_ACOMPANHAMENTO));
    }

    public function removerAcompanhamentoDestino(int $idAcompanhamento)
    {
        DB::beginTransaction();

        $acompanhamento = AcompanhamentoTemp::buscarDadosAcompanhamentoPorId($idAcompanhamento);

        if ($acompanhamento->situacao === 'PAUSADO') {
            throw new BadRequestHttpException('Não é possível remover um acompanhamento pausado.');
        }

        $acompanhamento->delete();

        DB::commit();
    }

    public function listarAcompanhamentoDestino(AcompanhamentoTempService $acompanhamento)
    {
        $resposta = $acompanhamento->listarAcompanhamentoDestino();

        return $resposta;
    }

    public function listarAcompanhamentoParaSeparar(AcompanhamentoTempService $acompanhamento)
    {
        $resposta = $acompanhamento->listarAcompanhamentoParaSeparar();

        return $resposta;
    }

    public function listarAcompanhamentoConferidos()
    {
        $resposta = AcompanhamentoTemp::listarAcompanhamentoConferidos();

        return $resposta;
    }

    public function listarAcompanhamentoEntregasAbertas()
    {
        $resposta = AcompanhamentoTemp::listarAcompanhamentoEntregasAbertas();

        return $resposta;
    }

    public function pausarAcompanhamento(string $uuidProduto)
    {
        $resultado = LogisticaItemService::buscaDadosParaAcompanhamentoPorUuid($uuidProduto);

        $acompanhamento = AcompanhamentoTemp::buscarAcompanhamentoDestino(
            $resultado['id_cliente'],
            $resultado['id_tipo_frete'],
            $resultado['id_cidade']
        );

        if (!empty($acompanhamento) && $acompanhamento['situacao'] === 'PAUSADO') {
            throw new BadRequestHttpException('Essa expedição já está pausada.');
        }

        if ($resultado['id_tipo_frete'] !== 2) {
            throw new BadRequestHttpException('Essa forma de envio não pode ser pausada.');
        }

        if ($resultado['possui_entrega']) {
            throw new BadRequestHttpException('Não é possível pausar uma expedição que já tenha uma entrega criada.');
        }

        $uuidProdutos = LogisticaItemModel::buscaProdutosParaAdicionarNoAcompanhamento(
            $resultado['id_cliente'],
            $resultado['id_tipo_frete'],
            $resultado['id_cidade']
        );

        dispatch(new GerenciarAcompanhamento($uuidProdutos, GerenciarAcompanhamento::PAUSAR_ACOMPANHAMENTO));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    public function despausarAcompanhamento()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'id_tipo_frete' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_cidade' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $dados['id_destinatario'] = Auth::user()->id_colaborador;

        $acompanhamento = AcompanhamentoTemp::buscarAcompanhamentoDestino(
            $dados['id_destinatario'],
            $dados['id_tipo_frete'],
            $dados['id_cidade']
        );

        if (empty($acompanhamento)) {
            throw new BadRequestHttpException('Não há acompanhamento pausado para esse destino.');
        }
        if ($acompanhamento['situacao'] !== 'PAUSADO') {
            throw new BadRequestHttpException('Esse acompanhamento está em uma situação que não pode ser alterado.');
        }

        $temLogisticaExternaPendente = LogisticaItemService::existeLogisticaExternaPendenteParaAcompanhamento(
            $dados['id_destinatario'],
            $dados['id_tipo_frete'],
            $dados['id_cidade']
        );

        if ($temLogisticaExternaPendente) {
            throw new BadRequestHttpException(
                "Não é possível retomar o acompanhamento porque existem produtos pendentes.\n\nEm caso de urgência, entre em contato com o suporte."
            );
        }

        $uuidProdutos = LogisticaItemModel::buscaProdutosParaAdicionarNoAcompanhamento(
            $dados['id_destinatario'],
            $dados['id_tipo_frete'],
            $dados['id_cidade']
        );

        dispatch(new GerenciarAcompanhamento($uuidProdutos, GerenciarAcompanhamento::DESPAUSAR_ACOMPANHAMENTO));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
