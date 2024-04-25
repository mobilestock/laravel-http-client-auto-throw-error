<?php

namespace MobileStock\jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\Middlewares\SetLogLevel;
use MobileStock\service\AcompanhamentoTempService;
use Psr\Log\LogLevel;

class GerenciarAcompanhamento implements ShouldQueue
{
    use Queueable;

    public const CRIAR_ACOMPANHAMENTO = 'CRIAR_ACOMPANHAMENTO';
    public const ADICIONAR_NO_ACOMPANHAMENTO = 'ADICIONAR_NO_ACOMPANHAMENTO';
    public const PAUSAR_ACOMPANHAMENTO = 'PAUSAR_ACOMPANHAMENTO';
    public const DESPAUSAR_ACOMPANHAMENTO = 'DESPAUSAR_ACOMPANHAMENTO';

    protected array $uuidsProdutos;
    protected int $idUsuario;
    protected string $acao;

    /**
     * @param string $acao [ 'ADICIONAR_NO_ACOMPANHAMENTO', 'CRIAR_ACOMPANHAMENTO', 'PAUSAR_ACOMPANHAMENTO', 'DESPAUSAR_ACOMPANHAMENTO' ]
     */
    public function __construct(array $uuids, string $acao = self::ADICIONAR_NO_ACOMPANHAMENTO, int $idUsuario = 0)
    {
        $this->uuidsProdutos = $uuids;
        $this->acao = $acao;
        $this->idUsuario = $idUsuario ?: Auth::id();

        $this->middleware[] = SetLogLevel::class . ':' . LogLevel::CRITICAL;
    }

    public function handle(AcompanhamentoTempService $acompanhamentoTempService)
    {
        DB::beginTransaction();
        $listaDeProdutosPendentes = $acompanhamentoTempService->buscaAcompanhamentoPendentePorUuidProduto(
            $this->uuidsProdutos
        );

        foreach ($listaDeProdutosPendentes as $acompanhamento) {
            if (
                $acompanhamento['id_acompanhamento'] === 0 &&
                !in_array($this->acao, [self::PAUSAR_ACOMPANHAMENTO, self::CRIAR_ACOMPANHAMENTO])
            ) {
                continue;
            }

            if ($acompanhamento['id_acompanhamento'] === 0) {
                $acompanhamento['id_acompanhamento'] = $acompanhamentoTempService->criaAcompanhamento(
                    $acompanhamento['id_destinatario'],
                    $acompanhamento['id_tipo_frete'],
                    $this->idUsuario,
                    $acompanhamento['id_cidade']
                );
            }
            $produtosPendentes = array_filter(
                $acompanhamento['uuids_produtos'],
                fn($item) => !$item['esta_no_acompanhamento']
            );
            if (!empty($produtosPendentes)) {
                $acompanhamentoTempService->adicionaItemAcompanhamento(
                    array_column($produtosPendentes, 'uuid_produto'),
                    $acompanhamento['id_acompanhamento'],
                    $this->idUsuario
                );
            }
            $acompanhamentoTempService->determinaNivelDoAcompanhamento(
                $acompanhamento['id_acompanhamento'],
                $this->acao
            );
        }
        $acompanhamentoTempService->removeAcompanhamentoSemItems();
        DB::commit();
    }
}
