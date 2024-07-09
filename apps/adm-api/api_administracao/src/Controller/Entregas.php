<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\model\Entrega;
use MobileStock\model\EntregasFaturamentoItem;
use MobileStock\model\ProdutoModel;
use MobileStock\service\EntregaService\EntregaServices;
use MobileStock\service\EntregaService\EntregasFaturamentoItemService;
use MobileStock\service\TipoFreteService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceirasMetadadosService;
use PDO;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Entregas extends Request_m
{
    public function buscaEntregaPorID(int $idEntrega)
    {
        $dadosEntrega = EntregaServices::buscarEntregaPorID($idEntrega);

        foreach ($dadosEntrega['etiquetas'] as &$etiqueta) {
            $etiqueta = Globals::geraQRCODE($etiqueta);
        }

        foreach ($dadosEntrega['produtos'] as &$produto) {
            $produto['qrcode'] = Globals::geraQRCODE(
                'produto/' . $produto['id_produto'] . '?w=' . $produto['uuid_produto']
            );
        }

        $dadosEntrega['ids_produtos_frete'] = [
            ProdutoModel::ID_PRODUTO_FRETE,
            ProdutoModel::ID_PRODUTO_FRETE_EXPRESSO,
            ProdutoModel::ID_PRODUTO_FRETE_VOLUME,
        ];

        return $dadosEntrega;
    }

    public function buscaDetalhesEntrega(int $idEntrega)
    {
        $informacoes = EntregasFaturamentoItemService::informacoesRelatorioEntregadores($idEntrega);

        return $informacoes;
    }

    public function buscaItensDasEntregasEntregues()
    {
        $dadosJson = Request::all();

        Validador::validar($dadosJson, [
            'pesquisa' => [],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $resposta = EntregasFaturamentoItemService::buscaItensDasEntregasEntregues(
            $dadosJson['pesquisa'],
            $dadosJson['pagina']
        );

        return $resposta;
    }

    public function alterarDataBaseDaTroca()
    {
        DB::beginTransaction();
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'uuid_produto' => [Validador::OBRIGATORIO],
            'nova_data' => [Validador::OBRIGATORIO, Validador::DATA],
        ]);
        $entregaItem = new EntregasFaturamentoItem();
        $entregaItem->exists = true;

        $entregaItem->data_base_troca = $dadosJson['nova_data'];
        $entregaItem->uuid_produto = $dadosJson['uuid_produto'];

        $entregaItem->update();
        DB::commit();
    }

    public function alterarTipoFreteDaEntrega(EntregaServices $entregaServices)
    {
        DB::beginTransaction();

        $dadosJson = Request::all();

        Validador::validar($dadosJson, [
            'id_entrega' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_tipo_frete' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        /**
         * Se surgir a demanda de precisar alterar o tipo_frete
         *  em algum outro lugar deverá sempre executar a seguinte lógica,
         * ou seja, englobar ela no EntregaServices e fazer teste automatizado:
         */
        $dadosTipoFrete = TipoFreteService::buscaDadosPonto($dadosJson['id_tipo_frete']);

        if ($dadosTipoFrete['tipo_ponto'] !== 'PM') {
            throw new BadRequestException('O tipo de frete selecionado não é permitido para entregas');
        }

        $idRaio = $entregaServices->buscaRaioMaisProximoDoEntregador(
            $dadosJson['id_entrega'],
            $dadosTipoFrete['id_colaborador']
        );

        if (empty($idRaio)) {
            throw new NotFoundHttpException('Este entregador não pode coletar a entrega para a cidade selecionada');
        }

        $entrega = new Entrega();
        $entrega->exists = true;
        $entrega->id = $dadosJson['id_entrega'];
        $entrega->id_raio = $idRaio;
        $entrega->id_tipo_frete = $dadosJson['id_tipo_frete'];
        $entrega->id_cliente = $dadosTipoFrete['id_colaborador'];
        $entrega->update();

        $transacoes = EntregasFaturamentoItem::buscaTransacoesDaEntrega($dadosJson['id_entrega']);
        foreach ($transacoes as $idTransacao) {
            $chavesMetadadosExistentes = TransacaoFinanceirasMetadadosService::buscaChavesTransacao($idTransacao);
            $metadadosEndereco = $chavesMetadadosExistentes['ENDERECO_CLIENTE_JSON'];
            if (empty($metadadosEndereco)) {
                continue;
            }

            $metadadosEndereco['valor']['id_raio'] = $idRaio;
            $metadados = new TransacaoFinanceirasMetadadosService();
            $metadados->id = $metadadosEndereco['id'];
            $metadados->id_transacao = $idTransacao;
            $metadados->chave = 'ENDERECO_CLIENTE_JSON';
            $metadados->valor = $metadadosEndereco['valor'];
            $metadados->alterar(DB::getPdo());
        }

        DB::commit();
    }

    public function letrasPontosMaisUsados(PDO $conexao)
    {
        $resposta = EntregaServices::letrasMaisUsadas($conexao);
        $total = array_sum(array_column($resposta, 'quantidade'));

        $resposta = array_map(function (array $item) use ($total) {
            $item['quantidade'] = (int) $item['quantidade'];
            $item['clientes'] = json_decode($item['clientes'], true);
            $item['percentual'] = round($total > 0 ? ($item['quantidade'] / $total) * 100 : 0, 2);

            return $item;
        }, $resposta);

        return ['resposta' => $resposta, 'total' => $total];
    }

    public function buscaProdutosEntregaAtrasada()
    {
        $logisticasAtrasandoPagamentoSeller = EntregasFaturamentoItemService::buscaInfosProdutosEntregasAtrasadas();

        return $logisticasAtrasandoPagamentoSeller;
    }
}
