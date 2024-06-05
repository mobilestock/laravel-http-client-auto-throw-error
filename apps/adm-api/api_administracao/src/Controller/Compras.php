<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Error;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;
use MobileStock\helper\Validador;
use MobileStock\service\Compras\ComprasService;
use MobileStock\service\Compras\MovimentacoesService;
use MobileStock\service\Estoque\EstoqueGradeService;
use PDO;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Compras extends Request_m
{
    private ComprasService $comprasService;
    private MovimentacoesService $movimentacoesService;

    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO;
        parent::__construct();

        $this->movimentacoesService = new MovimentacoesService($this->conexao);
        $this->comprasService = new ComprasService($this->conexao);
    }

    public function entradaCompras(): void
    {
        try {
            $this->conexao->beginTransaction();

            // $tamanhoFoto = $this->request->get('tamanho');
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );

            $jsonData = json_decode($this->json, true);
            Validador::validar($jsonData, [
                'codigos' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            $entradasComErro = [];
            foreach ($jsonData['codigos'] as $codigo) {
                $responsavel = ComprasService::verificaResponsavelPorBarCodeCompra($this->conexao, $codigo);

                Validador::validar($responsavel, [
                    'id_compra' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'permitido_reposicao' => [Validador::OBRIGATORIO, Validador::NUMERO],
                ]);

                switch (true) {
                    case $responsavel['permitido_reposicao'] != 1:
                        throw new Exception('Esse produto não tem permissão para reposição');
                    case !is_null($responsavel['id_responsavel']) &&
                        (int) $responsavel['id_responsavel'] != 1 &&
                        $responsavel['permitido_reposicao'] != 1:
                        throw new Exception('Esse produto pertence ao estoque externo');
                    case !$responsavel['concluiu_reposicao']:
                        ComprasService::concluiReposicao($this->conexao, $responsavel['id_compra']);
                        break;
                }

                $dadosCodBarras = $this->comprasService->consultaDadosCodBarras($codigo);
                $idMovimencao = $this->movimentacoesService->getIdMovimentacao();

                $sql = '';

                if (empty($dadosCodBarras)) {
                    $entradasComErro[$codigo] = 'Essa caixa não existe no sistema';
                    continue;
                }

                foreach ($dadosCodBarras as $codBarra) {
                    ComprasService::verificaSePermitido($this->conexao, $codBarra['id_produto']);

                    $estoque = new EstoqueGradeService();
                    $estoque->pares = (int) $codBarra['quantidade'];
                    // $estoque->tamanho_foto = (string) $tamanhoFoto;
                    $sql .= $estoque->retornaSqlAdicionarAguardEstoque(
                        $this->idUsuario,
                        $codBarra['id_produto'],
                        $codBarra['nome_tamanho'],
                        $codBarra['id_compra']
                    );

                    $sql .= $this->comprasService->baixaCaixa($codigo, $this->idUsuario, $idMovimencao);

                    $sql .= $this->movimentacoesService->insereMovimentacaoEstoqueItem(
                        $idMovimencao,
                        $codBarra['id_produto'],
                        $codBarra['nome_tamanho'],
                        $codBarra['quantidade'],
                        $codBarra['id_compra'],
                        $codBarra['id_sequencia'],
                        $codBarra['volume'],
                        $codBarra['preco_unit']
                    );
                }

                $sql .= $this->movimentacoesService->insereHistoricoDeMovimentacao($idMovimencao, $this->idUsuario);

                $this->conexao->exec($sql);

                $this->comprasService->atualizaSituacaoDaCompra($dadosCodBarras[0]['id_compra']);

                $this->comprasService->salvaHistoricoEntradaCaixa($codigo, $codBarra['id_fornecedor'], 1);
            }

            $this->conexao->commit();
            $this->retorno['message'] = 'Entrada realizada com sucesso!';
            $this->retorno['data'] = [
                'entradas' => $dadosCodBarras,
                'entradas_com_erro' => $entradasComErro,
            ];
            $this->codigoRetorno = 200;
        } catch (\Throwable $exception) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;

            if (isset($codBarra['id_fornecedor'])) {
                $this->comprasService->salvaHistoricoEntradaCaixa($codigo, $codBarra['id_fornecedor'], 0);
            }
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaCodigoBarrasCompra($dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'id_compra' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = ComprasService::buscaCodigoBarrasCompra($this->conexao, $dadosJson['id_compra']);
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function salvarCompra(PDO $conexao, Request $request)
    {
        try {
            $conexao->beginTransaction();

            $dadosJson = $request->all();

            Validador::validar($dadosJson, [
                'id_fornecedor' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'data_previsao' => [Validador::OBRIGATORIO],
                'produtos' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            Validador::validar($dadosJson['produtos'], [
                'children' => [Validador::OBRIGATORIO, Validador::ARRAY],
                'inputsGrade' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            Validador::validar($dadosJson['produtos']['inputsGrade'], [
                'caixas' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'novaGrade' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            foreach ($dadosJson['produtos']['children'] as $produto) {
                ComprasService::verificaSePermitido($conexao, $produto['id']);
            }

            if ($dadosJson['id_compra']) {
                //editar compra
                if (($dadosJson['produtos']['situacao'] ?? 0) == 1) {
                    ComprasService::updateCompra(
                        $conexao,
                        $dadosJson['id_compra'],
                        $dadosJson['id_fornecedor'],
                        $dadosJson['data_previsao']
                    );
                    ComprasService::excluirItemCompra($dadosJson['id_compra'], $dadosJson['produtos']);
                }
            } else {
                //salvar compra
                $dadosJson['id_compra'] = (int) ComprasService::buscaUltimaCompra($conexao) + 1;
                ComprasService::insereNovaCompra($conexao, $dadosJson);
            }

            Validador::validar($dadosJson, [
                'id_compra' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $sequencia = ComprasService::buscaUltimaSequenciaCompra($conexao, $dadosJson['id_compra']);
            $sequencia++;

            $grades = ComprasService::buscaGradeProduto($conexao, $dadosJson['produtos']['id']);
            $gradePreenchida = [];

            foreach ($grades as $key => $grade) {
                $gradePreenchida[$key] = [
                    'nome_tamanho' => (string) $grade['nome_tamanho'],
                    'quantidade' => (int) array_values(
                        array_filter($dadosJson['produtos']['inputsGrade']['novaGrade'], function ($novaGrade) use (
                            $grade
                        ) {
                            return $novaGrade['nome_tamanho'] == $grade['nome_tamanho'];
                        })
                    )[0]['quantidade'],
                    'produto' => (int) $dadosJson['produtos']['id'],
                ];
            }

            if (
                ComprasService::insereCompraProdutosGrade(
                    $conexao,
                    $dadosJson['id_compra'],
                    $gradePreenchida,
                    $dadosJson['produtos']['inputsGrade']['caixas'],
                    $sequencia
                )
            ) {
                $idProdutoTemp = '';
                if (
                    ComprasService::insereCompraProdutos(
                        $conexao,
                        $dadosJson['produtos']['id'],
                        $sequencia,
                        $dadosJson['id_compra'],
                        $dadosJson['produtos']['valor_custo_produto'],
                        $dadosJson['produtos']['inputsGrade']['caixas'],
                        1,
                        $dadosJson['produtos']['quantidadeTotal'],
                        $dadosJson['produtos']['valorTotal']
                    )
                ) {
                    if ($idProdutoTemp != $dadosJson['produtos']['id']) {
                        $idProdutoTemp = $dadosJson['produtos']['id'];

                        if (
                            !ComprasService::insereCompraCodigoBarras(
                                $conexao,
                                $dadosJson['id_fornecedor'],
                                $dadosJson['produtos']['id'],
                                $dadosJson['id_compra'],
                                $sequencia,
                                $dadosJson['produtos']['inputsGrade']['caixas']
                            )
                        ) {
                            throw new Error('Erro ao tentar salvar os código de barras');
                        }
                    }
                }
            } else {
                throw new Error('Erro ao tentar inserir grades do produto');
            }

            // busca dados para atualizar tela:
            $listaProdutos = ComprasService::formataListaProdutosCompra(
                $conexao,
                $dadosJson['id_fornecedor'],
                $dadosJson['id_compra']
            );

            $arrayDadosProdutos = [
                'id_compra' => $dadosJson['id_compra'],
                'listaDemandaProdutos' => $listaProdutos['demanda'] ?? [],
                'listaProdutosAdicionados' => $listaProdutos['adicionados'] ?? [],
            ];

            $conexao->commit();

            return $arrayDadosProdutos;
        } catch (\Throwable $ex) {
            $conexao->rollBack();
            throw $ex;
        }
    }
    public function buscaUmaCompra(PDO $conexao, int $idCompra)
    {
        $compra = ComprasService::buscaInformacoesDaCompra($idCompra);
        $listaProdutos = ComprasService::formataListaProdutosCompra($conexao, $compra['id_fornecedor'], $idCompra);
        if (sizeof($listaProdutos['demanda']) > 500) {
            $listaProdutos['demanda'] = array_slice($listaProdutos['demanda'], 0, 500);
        }

        $dadosProdutos = [
            'listaDemandaProdutos' => $listaProdutos['demanda'] ?? [],
            'listaProdutosAdicionados' => $listaProdutos['adicionados'] ?? [],
            'id_fornecedor' => $compra['id_fornecedor'],
            'situacao' => $compra['situacao'],
            'data_previsao' => $compra['data_previsao'],
            'edicao_fornecedor' => $compra['edicao_fornecedor'],
        ];

        return $dadosProdutos;
    }
    public function buscaProdutosReposicaoInterna(int $idFornecedor)
    {
        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'pesquisa' => [Validador::NAO_NULO],
        ]);

        $retorno = ComprasService::buscaDemandaProdutosFornecedor($idFornecedor, $dadosJson['pesquisa'], true);

        return $retorno;
    }
    public function buscaEtiquetasUnitariasCompra($dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'id_compra' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = ComprasService::buscaEtiquetasUnitarias($this->conexao, $dadosJson['id_compra']);
            $this->retorno['message'] = 'Etiquetas unitárias encontradas com sucesso';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->retorno['message'] = $ex->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaEtiquetasColetivasCompra($dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'id_compra' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $this->retorno['data'] = ComprasService::dadosEtiquetaColetiva($this->conexao, $dadosJson['id_compra']);
            $this->retorno['message'] = 'Etiquetas encontradas!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->retorno['message'] = $ex->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaDadosCodBarras($dadosJson)
    {
        try {
            Validador::validar($dadosJson, [
                'codigo_barras' => [Validador::OBRIGATORIO],
            ]);

            $this->retorno['data'] = ComprasService::infosPorCodBarras($this->conexao, $dadosJson['codigo_barras']);
            $this->retorno['message'] = 'Informações encontradas com sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->retorno['message'] = $ex->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaListaCompras()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'itens' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            if ($this->nivelAcesso == 30) {
                $dadosJson['id_fornecedor'] = (int) $this->idCliente;
            }

            $this->retorno['data'] = ComprasService::consultaListaCompras($this->conexao, $dadosJson);
            $this->retorno['message'] = 'Lista de compras encontradas com sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->retorno['message'] = $ex->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaHistoricoDadosCodBarras()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'codigos' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);

            $this->retorno['data'] = ComprasService::infosImprimirHistoricoCompras(
                $this->conexao,
                $dadosJson['codigos']
            );
            $this->retorno['message'] = 'Informações buscadas com sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->retorno['message'] = $ex->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaUltimasEntradasCompra()
    {
        try {
            $this->retorno['data'] = ComprasService::relatorioUltimasCompras($this->conexao);
            $this->retorno['message'] = 'Relatórios buscadas com sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->retorno['message'] = $ex->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function removeItemReposicao(int $idCompra)
    {
        DB::beginTransaction();

        $dadosJson = FacadesRequest::all();
        Validador::validar($dadosJson, [
            'id_produto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'sequencia' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $comprasItens = ComprasService::buscaDetalhesComprasItens($idCompra);
        foreach ($comprasItens as $compraItem) {
            $novaSituacao = null;

            switch (true) {
                case empty($compraItem['caixas_entregues']) &&
                    $compraItem['id_situacao'] !== ComprasService::SITUACAO_ABERTO:
                    $novaSituacao = ComprasService::SITUACAO_ABERTO;
                    break;
                case $compraItem['caixas'] === $compraItem['caixas_entregues'] &&
                    $compraItem['id_situacao'] !== ComprasService::SITUACAO_ENTREGUE:
                    $novaSituacao = ComprasService::SITUACAO_ENTREGUE;
                    break;
                case !empty($compraItem['caixas_entregues']) &&
                    $compraItem['caixas'] > $compraItem['caixas_entregues'] &&
                    $compraItem['id_situacao'] !== ComprasService::SITUACAO_PARCIALMENTE_ENTREGUE:
                    $novaSituacao = ComprasService::SITUACAO_PARCIALMENTE_ENTREGUE;
                    break;
            }

            if ($novaSituacao !== null) {
                ComprasService::atualizaSituacaoCompraItem($idCompra, $compraItem['sequencia'], $novaSituacao);
            }
        }

        $novaSituacao = null;
        $compra = ComprasService::buscaInformacoesDaCompra($idCompra);
        $comprasItensEntregues = array_filter(
            $comprasItens,
            fn($compraItem) => $compraItem['id_situacao'] === ComprasService::SITUACAO_ENTREGUE
        );
        switch (true) {
            case empty($comprasItensEntregues) && $compra['situacao'] !== ComprasService::SITUACAO_ABERTO:
                $novaSituacao = ComprasService::SITUACAO_ABERTO;
                break;
            case count($comprasItens) === count($comprasItensEntregues) &&
                $compra['situacao'] !== ComprasService::SITUACAO_ENTREGUE:
                $novaSituacao = ComprasService::SITUACAO_ENTREGUE;
                break;
            case !empty($comprasItensEntregues) &&
                count($comprasItens) > count($comprasItensEntregues) &&
                $compra['situacao'] !== ComprasService::SITUACAO_PARCIALMENTE_ENTREGUE:
                $novaSituacao = ComprasService::SITUACAO_PARCIALMENTE_ENTREGUE;
                break;
        }
        if ($novaSituacao !== null) {
            ComprasService::atualizaSituacaoCompra($idCompra, $novaSituacao);
        }

        ComprasService::excluirItemCompra($idCompra, [
            'id' => $dadosJson['id_produto'],
            'sequencia' => $dadosJson['sequencia'],
        ]);

        DB::commit();
    }
    public function concluirReposicao(int $idCompra)
    {
        ComprasService::geraTravaConcluirReposicao($idCompra);
        DB::beginTransaction();
        $compra = ComprasService::buscaInformacoesDaCompra($idCompra);
        if ($compra['edicao_fornecedor']) {
            throw new ConflictHttpException('Reposição já concluída!');
        }

        ComprasService::concluiReposicao(DB::getPdo(), $idCompra);
        DB::commit();
    }
}
