<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Globals;
use MobileStock\helper\Validador;
use MobileStock\repository\ColaboradoresRepository;
// use MobileStock\service\Publicacao\PublicacoesComentariosService;
use MobileStock\service\Publicacao\PublicacoesProdutosService;
use MobileStock\service\Publicacao\PublicacoesService;

class Publicacoes extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    // public function cadastro()
    // {
    //     try {
    //         $this->conexao->beginTransaction();

    //         $dadosJson = $this->request->request->all();

    //         // Validador::validar($dadosJson, [
    //         //     'descricao' => [Validador::OBRIGATORIO],
    //         //     //'produtos' => [Validador::OBRIGATORIO]
    //         // ]);

    //         if (isset($dadosJson['produtos']) && $dadosJson['produtos'] != "")
    //             $dadosJson['produtos'] = explode(',', $dadosJson['produtos']);

    //         $quantidadeMaximaProdutos = ConfiguracaoService::buscaQuantidadeMaximaDeProdutos($this->conexao);

    //         if (sizeof($dadosJson['produtos']) > $quantidadeMaximaProdutos) {
    //             throw new \InvalidArgumentException("Limite de $quantidadeMaximaProdutos produtos por publicação!");
    //         }

    //         Validador::validar($_FILES, [
    //             'foto_publicacao' => [Validador::OBRIGATORIO]
    //         ]);

    //         if (!ColaboradoresRepository::colaboradorTemCidadeCadastrada($this->conexao, $this->idCliente)) {
    //             throw new \InvalidArgumentException('Para criar uma publicação é necessário primeiro preencher uma cidade no seu cadastro');
    //         }

    //         $publicacao = new PublicacoesService();
    //         $publicacao->id_colaborador = $this->idCliente;
    //         $publicacao->tipo_publicacao = 'ML';
    //         $publicacao->descricao = $dadosJson['descricao'];
    //         $caminhoImagem = $publicacao->insereFoto($_FILES['foto_publicacao']);
    //         $publicacao->salva($this->conexao);

    //         foreach ($dadosJson['produtos'] as $produto) {
    //             $produtos = new PublicacoesProdutosService();

    //             $produto = (array) JWT::decode($produto, Globals::JWT_KEY, ['HS256']);

    //             if(mb_strlen(($mensagem = PublicacoesProdutosService::produtosEstaDisponivelParaPublicacao($this->conexao, $produto['uuid']))) > 0) {
    //                 throw new \InvalidArgumentException($mensagem);
    //             }

    //             $produtos->id_publicacao = $publicacao->id;
    //             $produtos->id_produto = $produto['id'];
    //             $produtos->foto_publicacao = $caminhoImagem;
    //             $produtos->uuid = $produto['uuid'];
    //             $produtos->salva($this->conexao);
    //         }

    //         $this->conexao->commit();
    //         $this->retorno['message'] = 'Publicacão salva com sucesso!';
    //         $this->retorno['data']['publicacao'] = $publicacao;
    //         $this->status = 200;
    // 	} catch(\PDOException $pdoException) {
    //         $this->conexao->rollBack();

    //         if (isset($publicacao) && $publicacao->foto) {
    //             $publicacao->removeFoto($publicacao->foto);
    //         }

    //         $this->status = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $pdoException->getMessage();

    //         $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
    //     } catch (\Throwable $ex) {
    //         $this->conexao->rollback();

    //         if (isset($publicacao) && isset($publicacao->foto) && $publicacao->foto) {
    //             $publicacao->removeFoto($publicacao->foto);
    //         }

    // 		$this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
    // 		$this->status = 400;
    // 	} finally {
    // 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    // 	}
    // }

    /**
     * @deprecated
     * @see issue: https://github.com/mobilestock/web/issues/3088
     */
    public function criaPublicacaoStorie()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($_FILES, [
                'foto_publicacao' => [Validador::OBRIGATORIO],
            ]);

            $dados = $this->request->request->all();
            $produtos = json_decode($dados['produtos'], true);

            // $quantidadeMaximaProdutos = ConfiguracaoService::buscaQuantidadeMaximaDeProdutos($this->conexao);
            // if (sizeof($produtos) > $quantidadeMaximaProdutos) {
            //     throw new \InvalidArgumentException("Limite de $quantidadeMaximaProdutos produtos por storie!");
            // }

            $publicacao = new PublicacoesService();
            $publicacao->id_colaborador = $this->idCliente;
            $publicacao->tipo_publicacao = 'ST';
            $publicacao->descricao = $dados['descricao'];
            $publicacao->insereFoto($_FILES['foto_publicacao']);
            $publicacao->salva($this->conexao);

            $produtosCard = [];
            foreach ($produtos as $produto) {
                $produtosService = new PublicacoesProdutosService();
                $produtoDecoded = (array) JWT::decode($produto['uuid_produto'], Globals::JWT_KEY, ['HS256']);

                // if(mb_strlen(($mensagem = PublicacoesProdutosService::produtosEstaDisponivelParaPublicacao($this->conexao, $produtoDecoded['uuid']))) > 0) {
                //     throw new \InvalidArgumentException($mensagem);
                // }

                $produtosService->id_publicacao = $publicacao->id;
                $produtosService->id_produto = $produtoDecoded['id'];
                $produtosService->uuid = $produtoDecoded['uuid'];
                $produtosService->foto_publicacao = $publicacao->foto;
                $id_produto_publicacao = $produtosService->salva($this->conexao);

                $produtoCard = (object) [
                    'id_produto' => (int) $produto['id'],
                    'id_produto_publicacao' => (int) $id_produto_publicacao,
                    'pos_x' => $produto['positionX'],
                    'pos_y' => $produto['positionY'],
                ];

                $produtosCard[] = $produtoCard;
            }
            PublicacoesProdutosService::criaStoryProcessado(
                $this->conexao,
                $publicacao->id,
                json_encode($produtosCard)
            );

            $this->conexao->commit();
            $this->retorno['message'] = 'Storie postado com sucesso!';
            $this->retorno['data']['publicacao'] = $publicacao;
            $this->status = 200;
        } catch (\PDOException $pdoException) {
            $this->conexao->rollBack();

            if (isset($publicacao) && $publicacao->foto) {
                $publicacao->removeFoto($publicacao->foto);
            }

            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $pdoException->getMessage();

            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
        } catch (\Throwable $ex) {
            $this->conexao->rollback();

            if (isset($publicacao, $publicacao->foto) && $publicacao->foto) {
                $publicacao->removeFoto($publicacao->foto);
            }

            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
            exit();
        }
    }

    //public function produtosParaPostagem()
    //{
    //    try {
    //        $dadosGet = $this->request->query->all();

    //        Validador::validar($dadosGet, [
    //            'pesquisa' => [Validador::NAO_NULO, Validador::SANIZAR]
    //        ]);

    //        $this->retorno['data']['produtos'] = ProdutosRepository::buscaProdutosParaPostagem($this->conexao, $this->idCliente, $dadosGet['pesquisa'], $this->request->get('pagina', 1));

    //        $this->retorno['message'] = 'Produtos buscados com sucesso!!';
    //        $this->status = 200;
    //	} catch(\PDOException $pdoException) {
    //        $this->status = 500;
    //        $this->retorno['status'] = false;
    //        $this->retorno['message'] = $pdoException->getMessage();

    //        $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
    //    } catch (\Throwable $ex) {
    //		$this->retorno['status'] = false;
    //        $this->retorno['message'] = $ex->getMessage();
    //		$this->status = 400;
    //	} finally {
    //		$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //        exit;
    //	}
    //}

    public function remove(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $idPublicacao = $dados['id'];
            $ownerID = ColaboradoresRepository::buscaDonoPublicacao($this->conexao, $idPublicacao);
            session_start();

            if ($ownerID !== $this->idCliente) {
                $permissoes = ColaboradoresRepository::buscaPermissaoUsuario($this->conexao, $this->idCliente);
                if (!in_array('INTERNO', $permissoes)) {
                    throw new \InvalidArgumentException('Usuário não tem permissão para remover publicações');
                    return;
                }
            }

            $publicacao = new PublicacoesService();
            $publicacao->id = $idPublicacao;
            $publicacao->situacao = 'RE';
            $publicacao->salva($this->conexao);

            $this->retorno['message'] = 'Publicacão removida com sucesso!';
            $this->status = 200;
        } catch (\PDOException $pdoException) {
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $pdoException->getMessage();

            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
            exit();
        }
    }

    public function listaVendasPublicacoes()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'filtro' => [Validador::OBRIGATORIO, Validador::ENUM('TROCAS_AGENDADAS', 'VENDAS_PENDENTES', 'VENDAS_FINALIZADAS')],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'data_inicial' => [Validador::SE(Validador::OBRIGATORIO, Validador::DATA)],
            'data_final' => [Validador::SE(Validador::OBRIGATORIO, Validador::DATA)]
        ]);

        $pagina = $dados['pagina'];
        $filtro = $dados['filtro'];
        $dataInicial = $dados['data_inicial'];
        $dataFinal = $dados['data_final'];

        $dados = $filtro === 'TROCAS_AGENDADAS'
            ? PublicacoesService::consultaComissoesTroca($pagina)
            : PublicacoesService::consultaVendasPublicacoes($pagina, $filtro, $dataInicial, $dataFinal);

        return $dados;
    }

    // public function buscaComissoesInfluencer(){
    //     try {
    //         $pagina = $this->request->query->get('pagina', 1);
    //         $this->retorno['data'] = PublicacoesService::consultaLooksFeed($this->conexao, $this->idCliente, $pagina, null);
    //         $this->retorno['message'] = 'Comissoes buscadas com sucesso!';
    //         $this->status = 200;

    //     } catch (\PDOException $pdoException) {
    //         $this->status = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $pdoException->getMessage();

    //     } finally {
    // 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    // public function novoComentario($dados)
    // {
    //     try {
    //         $json = json_decode($this->json, true);

    //         Validador::validar($json, [
    //             'comentario' => [Validador::OBRIGATORIO]
    //         ]);

    //         $publicacoesComentariosService = new PublicacoesComentariosService();
    //         $publicacoesComentariosService->id_colaborador = $this->idCliente;
    //         $publicacoesComentariosService->id_publicacao = $dados['id'];
    //         $publicacoesComentariosService->comentario = $json['comentario'];

    //         $this->retorno['data'] = $publicacoesComentariosService->adiciona($this->conexao);;
    //         $this->retorno['message'] = 'Comentário criado com sucesso!';
    //         $this->status = 200;

    //     } catch (\PDOException $pdoException) {
    //         $this->status = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $pdoException->getMessage();

    //     } catch (\Throwable $ex) {
    // 		$this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
    // 		$this->status = 400;

    //     } finally {
    // 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    // public function deletarComentario($dados)
    // {
    //     try {
    //         $publicacoesComentariosService = new PublicacoesComentariosService();
    //         $publicacoesComentariosService->id = $dados['idComentario'];
    //         $publicacoesComentariosService->id_colaborador = $this->idCliente;
    //         $publicacoesComentariosService->busca($this->conexao);

    //         if (!$publicacoesComentariosService->id)
    //             throw new Exception('Comentário não encontrado!');

    //         $this->retorno['data'] = $publicacoesComentariosService->remove($this->conexao);
    //         $this->retorno['message'] = 'Comentário deletado com sucesso!';
    //         $this->status = 200;

    //     } catch (\PDOException $pdoException) {
    //         $this->status = 500;
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $pdoException->getMessage();

    //     } catch (\Throwable $ex) {
    // 		$this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
    // 		$this->status = 400;

    //     } finally {
    // 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    public function alteraCurtirStories($dados)
    {
        try {
            Validador::validar($dados, [
                'id_publicacao' => [Validador::OBRIGATORIO],
            ]);

            $dadosJson = json_decode($this->json, true);

            $this->retorno['data'] = PublicacoesService::curteStories(
                $this->conexao,
                $dados['id_publicacao'],
                $this->idCliente,
                $dadosJson['like_id']
            );
            $this->retorno['message'] = 'Like alterado com sucesso!';
            $this->status = 200;
        } catch (\PDOException $pdoException) {
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $pdoException->getMessage();
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
            exit();
        }
    }

    public function consultaTotaisComissoes()
    {
        try {
            $this->retorno['data'] = PublicacoesService::consultaTotaisComissoes($this->conexao, $this->idCliente);
            $this->retorno['message'] = 'Totais de venda buscados com sucesso!';
            $this->status = 200;
        } catch (\PDOException $pdoException) {
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
            exit();
        }
    }
}
