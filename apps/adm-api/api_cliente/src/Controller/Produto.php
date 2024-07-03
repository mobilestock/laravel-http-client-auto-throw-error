<?php

namespace api_cliente\Controller;

use api_cliente\Models\Request_m;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\repository\FotosRepository;
use MobileStock\repository\ProdutosRepository;
use MobileStock\service\FAQService;
use MobileStock\service\ProdutoService;

class Produto extends Request_m
{
    private $conexao;

    public function __construct()
    {
        $this->nivelAcesso = '0';
        parent::__construct();
        $this->produtosRepository = new ProdutosRepository();
        $this->conexao = Conexao::criarConexao();
    }

    // public function busca($params)
    // {
    //     try {
    //         $conteudo = $this->produtosRepository->buscaProdutoEspecifico($this->conexao, $params['id']);
    //         $this->retorno['data'] = $conteudo;
    //     } catch (\Throwable $e) {
    //         $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //         die;
    //     }
    // }

    // public function lista()
    // {
    //     try {
    //         $pagina = $this->request->query->get('pagina') ?? 1;
    //         $conteudo = $this->produtosRepository->buscaProdutosCatalogo($this->conexao, '', $pagina, 100);
    //         $this->retorno['data'] = $conteudo;
    //     } catch (\Throwable $e) {
    //         $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //         die;
    //     }
    // }

    // public function buscaEstoque(array $params)
    // {
    //     try {
    //         Validador::validar($params, [
    //             "id" => [Validador::OBRIGATORIO, Validador::NUMERO]
    //         ]);

    //         $conteudo = ProdutosRepository::buscaEstoque($this->conexao, $params['id']);
    //         $this->retorno['data'] = $conteudo;
    //     } catch (\Throwable $e) {
    //         $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //         die;
    //     }
    // }

    // public function consultaCatalogo()
    // {
    //     try {
    //         $listaIds = [];

    //         $dadosPesquisa = [
    //             'linhas' => array_filter(explode(',', $this->request->query->get('linhas', ''))),
    //             'ordenar' => $this->request->query->get('ordenar', null),
    //             'pesquisa' => $this->request->query->get('pesquisa', ''),
    //             'pagina' => $this->request->query->get('pagina', 1),
    //             'tamanho' => array_filter(explode('-', $this->request->query->get('numeros', '')))
    //         ];

    //         $dadosPesquisa['pesquisa'] = str_replace('_', ' ', \str_replace('-', ' ', $dadosPesquisa['pesquisa']));

    //         $limitePesquisa = 100;
    //         $offsetPesquisa = ($dadosPesquisa['pagina'] * $limitePesquisa) - $limitePesquisa;

    //         if (is_numeric($dadosPesquisa['pesquisa']) && ProdutosRepository::existeProdutoComId($dadosPesquisa['pesquisa'], $this->conexao)) {
    //             $listaIds[] = (int)$dadosPesquisa['pesquisa'];
    //         } else if (!empty($dadosPesquisa['pesquisa'])) {
    //             $openSearch = new OpenSearchClient();
    //             $retorno = $openSearch->pesquisaMobileStock(
    //                                             ConversorStrings::removeAcentos($dadosPesquisa['pesquisa']),
    //                                             $offsetPesquisa,
    //                                             $limitePesquisa
    //                                             )->body['hits']['hits'];

    //             $totalDeProdutos = $openSearch->body['hits']['total']['value'];
    //             $limiteDePaginas = ceil($totalDeProdutos / $limitePesquisa);

    //             $listaIds = [...$listaIds, ...array_map('intval', array_column($retorno, '_id'))];

    //             if ($dadosPesquisa['pagina'] > $limiteDePaginas) {
    //                 return [];
    //             }
    //         }

    //         $produtos = ProdutoService::buscaProdutosMobileStock($this->conexao, $listaIds, $dadosPesquisa, $limitePesquisa, $offsetPesquisa);

    //         $this->retorno['data'] = $produtos;
    //     } catch (\Throwable $e) {
    //         $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //     }
    // }

    /**
     * Essa rota não retorna nada, ela é usada para ser chamada de maneira assíncrona pela rota de produto
     */
    public function acessaProduto(array $dados)
    {
        try {
            $json = array_merge($this->request->query->all(), $dados, ['id_colaborador' => $this->idCliente]);

            Validador::validar($json, [
                'origem' => [Validador::OBRIGATORIO, Validador::ENUM('MS', 'ML')],
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            ProdutosRepository::insereRegistroAcessoProduto(
                $this->conexao,
                $json['id'],
                $json['origem'],
                $json['id_colaborador']
            );
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
            die();
        }
    }

    // public function buscaFaqProdutos(array $dados)
    // {
    //     try {
    //         $this->nivelAcesso = 0;
    //         $this->retorno['data'] = FAQService::buscaFaqProduto($this->conexao, $dados);
    //     } catch (\Throwable $e) {
    //         $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //         die;
    //     }
    // }

    // public function criaDuvida()
    // {
    //     try {
    //         $this->nivelAcesso = 1;
    //         extract(json_decode($this->json, true));
    //         $faq = new FAQService();
    //         $faq->tipo = 'PR';
    //         $faq->pergunta = $pergunta;
    //         $faq->id_cliente = $this->idCliente;
    //         $faq->id_produto = intVal($id_produto);
    //         $produto = ProdutosRepository::buscaProduto($this->conexao, intVal($id_produto));
    //         $faq->id_fornecedor = intVal($produto['id_fornecedor']);
    //         if ($faq->inserir($this->conexao)) {
    //             $message = "Sucess";
    //         } else {
    //             throw new Exception('Erro ao inserir duvida');
    //         }
    //         $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => []])->send();
    //     } catch (\Throwable $e) {
    //         $data = "";
    //         $this->respostaJson->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])->setStatusCode(400)->send();
    //     }
    // }

    public function respondeDuvida()
    {
        try {
            $this->nivelAcesso = 1;
            $dadosJson = json_decode($this->json, true);
            extract($dadosJson);
            $faq = new FAQService();
            $faq->resposta = $resposta;
            $faq->id = (int) $id;

            if ($faq->responder($this->conexao)) {
                $message = 'Sucesso';
            } else {
                throw new Exception('Erro ao responder duvida');
            }
            $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => []])->send();
        } catch (\Throwable $e) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function insereSugestaoProduto()
    {
        try {
            $this->nivelAcesso = 1;
            extract($_FILES);
            $imagem = FotosRepository::salvarFotoAwsS3Tratada(
                $foto,
                md5(uniqid(rand(), true)) . 'sugestao_produto',
                'PADRAO'
            );
            ProdutoService::salvaSugestaoDeProduto($this->conexao, $imagem, $this->idCliente);
            $this->respostaJson
                ->setData([
                    'status' => true,
                    'message' => 'Sua sugestão de produto foi enviada com sucesso',
                    'data' => [],
                ])
                ->send();
        } catch (\Throwable $e) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function listarTrocasAgendadas()
    {
        $resposta = ProdutoService::buscaTrocasAgendadas();
        return $resposta;
    }

    // public function inserirTrocaAgendada()
    // {
    //     try {
    //         $this->conexao->beginTransaction();
    //         Validador::validar(["json" => $this->json], [
    //             "json" => [Validador::OBRIGATORIO, Validador::JSON]
    //         ]);

    //         $dadosJson = json_decode($this->json, true);
    //         Validador::validar($dadosJson, [
    //             "uuid_produto" => [Validador::OBRIGATORIO]
    //         ]);

    //         DevolucaoAgendadaService::salvaProdutoTrocaAgendada($this->conexao, $dadosJson["uuid_produto"], $this->idCliente);
    //         $this->conexao->commit();
    //         $this->codigoRetorno = 200;
    //         $this->retorno["status"] = true;
    //         $this->retorno["message"] = "Troca agendada com sucesso!";
    //     } catch (\Throwable $th) {
    //         $this->conexao->rollBack();
    //         $this->codigoRetorno = 400;
    //         $this->retorno["status"] = false;
    //         $this->retorno["data"] = null;
    //         $this->retorno["message"] = $th->getMessage();
    //     } finally {
    //         $this->respostaJson
    //             ->setData($this->retorno)
    //             ->setStatusCode($this->codigoRetorno)
    //             ->send();
    //     }
    // }

    // public function removeTrocaAgendada()
    // {
    //     try {
    //         $this->nivelAcesso = 1;
    //         $dadosJson = json_decode($this->json, true);
    //         $retorno = DevolucaoAgendadaService::removeProdutoTrocaAgendada($this->conexao, $dadosJson['produto'], $this->idCliente);
    //         $this->respostaJson->setData(['status' => true, 'message' => "Troca agendada removida", 'data' => $retorno])->send();
    //     } catch (\Throwable $e) {
    //         $data = "";
    //         $this->respostaJson->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])->setStatusCode(400)->send();
    //     }
    // }
    // public function verificaDefeito()
    // {
    //     try {
    //         $retorno = ProdutoService::buscaDefeitos($this->conexao, $this->idCliente);
    //         $this->retorno['data'] = $retorno;
    //     } catch (\Throwable $e) {
    //         $this->retorno['status'] = false;
    //         $this->codigoRetorno = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
    //         die;
    //     }
    // }
}
