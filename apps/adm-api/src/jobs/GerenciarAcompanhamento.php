<?php

namespace MobileStock\jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Auth\QueueAuth;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\model\AcompanhamentoTemp;
use MobileStock\model\LogisticaItemModel;
use MobileStock\service\AcompanhamentoTempService;
use Psr\Log\LogLevel;

class GerenciarAcompanhamento implements ShouldQueue
{
    use Queueable, QueueAuth;

    public const CRIAR_ACOMPANHAMENTO = 'CRIAR_ACOMPANHAMENTO';
    public const ADICIONAR_NO_ACOMPANHAMENTO = 'ADICIONAR_NO_ACOMPANHAMENTO';
    public const PAUSAR_ACOMPANHAMENTO = 'PAUSAR_ACOMPANHAMENTO';
    public const DESPAUSAR_ACOMPANHAMENTO = 'DESPAUSAR_ACOMPANHAMENTO';

    protected array $uuidsProdutos;
    protected string $acao;

    /**
     * @param string $acao [ 'ADICIONAR_NO_ACOMPANHAMENTO', 'CRIAR_ACOMPANHAMENTO', 'PAUSAR_ACOMPANHAMENTO', 'DESPAUSAR_ACOMPANHAMENTO' ]
     */
    public function __construct(array $uuids, string $acao = self::ADICIONAR_NO_ACOMPANHAMENTO)
    {
        $this->authKeys = ['id'];
        $this->uuidsProdutos = $uuids;
        $this->acao = $acao;

        $this->middleware[] = SetLogLevel::class . ':' . LogLevel::CRITICAL;
    }

    public function handle(AcompanhamentoTempService $acompanhamentoTempService)
    {
        DB::beginTransaction();
        $listaDeProdutosPendentes = LogisticaItemModel::buscaAcompanhamentoPendentePorUuidProduto($this->uuidsProdutos);

        foreach ($listaDeProdutosPendentes as $acompanhamento) {
            if (
                empty($acompanhamento['id_acompanhamento']) &&
                !in_array($this->acao, [self::PAUSAR_ACOMPANHAMENTO, self::CRIAR_ACOMPANHAMENTO])
            ) {
                continue;
            }

            if (empty($acompanhamento['id_acompanhamento'])) {
                $acompanhamentoModel = new AcompanhamentoTemp();
                $acompanhamentoModel->id_destinatario = $acompanhamento['id_destinatario'];
                $acompanhamentoModel->id_tipo_frete = $acompanhamento['id_tipo_frete'];
                $acompanhamentoModel->id_cidade = $acompanhamento['id_cidade'];
                $acompanhamentoModel->id_raio = $acompanhamento['id_raio'];
                $acompanhamentoModel->save();
                $acompanhamento['id_acompanhamento'] = $acompanhamentoModel->id;
            }
            $produtosPendentes = array_filter(
                $acompanhamento['uuids_produtos'],
                fn($item) => !$item['esta_no_acompanhamento']
            );
            if (!empty($produtosPendentes)) {
                $acompanhamentoTempService->adicionaItemAcompanhamento(
                    array_column($produtosPendentes, 'uuid_produto'),
                    $acompanhamento['id_acompanhamento']
                );
            }
            AcompanhamentoTemp::determinaNivelDoAcompanhamento($acompanhamento['id_acompanhamento'], $this->acao);
        }
        AcompanhamentoTemp::removeAcompanhamentoSemItems();
        DB::commit();
    }
}
