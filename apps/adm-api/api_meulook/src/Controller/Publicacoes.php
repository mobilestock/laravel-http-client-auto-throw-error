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
            'filtro' => [
                Validador::OBRIGATORIO,
                Validador::ENUM('TROCAS_AGENDADAS', 'VENDAS_PENDENTES', 'VENDAS_FINALIZADAS'),
            ],
            'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'data_inicial' => [Validador::SE(Validador::OBRIGATORIO, Validador::DATA)],
            'data_final' => [Validador::SE(Validador::OBRIGATORIO, Validador::DATA)],
        ]);

        $pagina = $dados['pagina'];
        $filtro = $dados['filtro'];
        $dataInicial = $dados['data_inicial'];
        $dataFinal = $dados['data_final'];

        $dados =
            $filtro === 'TROCAS_AGENDADAS'
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
