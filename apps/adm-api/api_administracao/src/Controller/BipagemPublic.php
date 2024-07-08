<?php
namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\helper\ValidacaoException;
use MobileStock\helper\Validador;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\repository\EstoqueRepository;
use MobileStock\repository\NotificacaoRepository;
use MobileStock\service\Estoque\EstoqueService;
use MobileStock\service\MessageService;

class BipagemPublic extends Request_m
{
    public function __construct($rota)
	{
        $this->nivelAcesso = Request_m::SEM_AUTENTICACAO;
		parent::__construct();
		$this->conexao = Conexao::criarConexaoSuper();
	}

    public function aguardandoGet()
	{
		$acao = null;
		$nivel = 0;
		$tamanhos = [];

		$erro = null;

		$id_produto = null;
		$localizacao = null;
		$numeracoes = null;

		$listaColaboradoresNotificacao = [];

		try {
			$json = $this->json;
			if ($json != '') $json = json_decode($json, true);
			else $json = [];

			$id_produto = $json['produto'] ?? '';
			$localizacao = $json['local'] ?? '';
			$numeracoes = $json['numeracoes_id'] ?? '';

			$cod = $this->request->get('local_cod_barras');
			if (isset($cod)) {
				$cod = explode('-', $cod);
				$id_produto = $cod[1];
				$localizacao = $cod[0];
				$numeracoes = $cod[2];
			}
			$localizacao = preg_replace('/[^0-9]/', '', $localizacao);

			$this->conexao->beginTransaction();

			Validador::validar(
				[
					'id_produto' => $id_produto,
					'localizacao' => $localizacao,
					'numeracoes' => $numeracoes
				],
				[
					'id_produto' => [Validador::OBRIGATORIO],
					'localizacao' => [Validador::OBRIGATORIO, Validador::NUMERO]
				]
			);

			$idCliente = $_SESSION['id_cliente'];
			$permissoesUsuario = ColaboradoresRepository::buscaPermissaoUsuario($this->conexao, $idCliente);

			if (!in_array('INTERNO', $permissoesUsuario)) {
				throw new Exception("Não possui permissão para realizar essa operação");
			}

			$quantidadeAguardandoEntrada = (int) EstoqueService::ConsultaEstoqueProduto($this->conexao, $id_produto);

			$tamanhos = EstoqueService::ConsultaProdutosPorTamanho($this->conexao, $id_produto, $numeracoes);

			if ($numeracoes) {
				EstoqueService::DeletaTabelaProdutoTemporaria($this->conexao);
				$nivel = 1;

				EstoqueService::CriaTabelaTemporaria($this->conexao, $numeracoes);
				$nivel = 2;
				$acao = 'Alterar localizacao';
			}

			EstoqueService::defineLocalizacaoProduto($this->conexao, $id_produto, $localizacao, $_SESSION['id_usuario'], $numeracoes);
			$nivel = 3;
			$acao = 'Entrada de estoque';

			EstoqueRepository::testaMovimentacaoEstoqueOcorreu($id_produto, $numeracoes, $quantidadeAguardandoEntrada);

			foreach($tamanhos as $t) {
				$tamanho = (string) $t["nome_tamanho"];
				EstoqueService::NotificaClientesReestoque($this->conexao, $id_produto, $tamanho);
			}

			if ($numeracoes) {
				$produtos = [];
				foreach ($tamanhos as $tamanho) array_push($produtos, [
					'id_produto' => $id_produto,
					'tamanho' => $tamanho["nome_tamanho"],
					'qtd_movimentado' => $tamanho['qtd']
				]);
				$listaColaboradoresNotificacao = EstoqueService::BuscaClientesComProdutosNaFilaDeEspera($this->conexao, $produtos);
			}

			$this->conexao->commit();

		} catch (\Throwable $exception) {
			$this->conexao->rollBack();
			$listaColaboradoresNotificacao = [];

			if ($exception instanceof ValidacaoException && $exception->getIndiceInvalido() === 'localizacao') {
				$acao = 'Localizacao inválida';
				$erro = $exception->getMessage();
			} else {
				$acao = 'Erro durante execução das queries';
				$erro = "Erro nível <b>{$nivel}</b><br>" . $exception->getMessage();
			}

			$this->retorno['status'] = false;
			$this->retorno['message'] = $exception->getMessage();
			$this->codigoRetorno = 400;
		}

		try {
			$messageService = new MessageService();

			foreach($listaColaboradoresNotificacao as $colaborador) {
				$messageService->sendImageWhatsApp(
					$colaborador['telefone'],
					$colaborador['foto'],
					$colaborador['mensagem']
				);

				$mensagemNotificacao = 'Produto que estava na sua fila de espera chegou! <a href="/carrinho">Ver carrinho</a>';
				NotificacaoRepository::enviar([
					'colaboradores' => [ $colaborador['id'] ],
					'mensagem' => $mensagemNotificacao,
					'tipoMensagem' => 'C',
					'titulo' => 'Reposição!',
					'destino' => 'ML',
					'imagem' => $colaborador['foto']
				], '');
			};

		} catch (\Throwable $exception) {
			NotificacaoRepository::enviarSemValidacaoDeErro([
                'colaboradores' => [ 1 ],
                'mensagem' => "Erro ao enviar notificação reposição whatsapp: " . $exception->getMessage(),
                'tipoMensagem' => 'Z',
                'titulo' => 'Erro notificação reposição',
                'imagem' => ''
            ], $this->conexao);
		}

		$mensagensErro = [
			'Alterar localizacao' => function () {
				$_SESSION['warning'] = 'Localizacao alterada com sucesso! Não foram adicinados pares.';
			},
			'Entrada de estoque' => function () {
				$_SESSION['success'] = "Localização inserida com sucesso e estoque atualizado.";
			},
			'Localizacao inválida' => function () {
				$_SESSION['danger'] = 'Não é possível colocar o produto em localização vazia.';
			},
			'Erro durante execução das queries' => function () use ($erro) {
				$_SESSION['danger'] = $erro ?? 'Erro interno durante a execução';
			},
			'Erro de validacao' => function () use ($erro) {
				$_SESSION['danger'] = $erro;
			}
		];
		$mensagensErro[$acao]();

		$urlBase = $_ENV['URL_MOBILE'];
		header("location:{$urlBase}/estoque-sem-localizacao.php");

		exit();
	}

}
