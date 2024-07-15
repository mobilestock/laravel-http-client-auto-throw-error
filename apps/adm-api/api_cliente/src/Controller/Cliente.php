<?php

namespace api_cliente\Controller;

use api_cliente\Models\Conect;
use api_cliente\Models\Request_m;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\RegrasAutenticacao;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\Origem;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\Cadastros\CadastrosService;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\IBGEService;
use MobileStock\service\PedidoItem\PedidoItemMeuLookService;
use MobileStock\service\TipoFreteService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraItemProdutoService;
use MobileStock\service\TransacaoFinanceira\TransacaoFinanceiraService;
use MobileStock\service\UsuarioService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Cliente extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '4';
        parent::__construct();
        $this->conexao = Conect::conexao();
    }

    public function buscaCidade($request)
    {
        try {
            if ($request['uf'] != '') {
                Validador::validar($request, [
                    'uf' => [Validador::OBRIGATORIO, Validador::STRING],
                ]);
                $uf = $request['uf'];
            } else {
                $uf = 0;
            }
            if ($cidades = IBGEService::buscarCidades($uf)) {
                $message = 'Cidades Encontradas com sucesso';
            } else {
                throw new Exception('Cidades não encontradas!', 1);
            }
            $this->retorno = ['status' => true, 'message' => $message, 'data' => $cidades];
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

    public function buscaUF()
    {
        try {
            if ($estados = IBGEService::buscarUF()) {
                $message = 'Estados Encontrados com sucesso';
            } else {
                throw new Exception('Estados não encontrados!', 1);
            }
            $this->retorno['data'] = $estados;
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

    /**
     * @issue: https://github.com/mobilestock/backend/issues/89
     */
    public function editPassword()
    {
        $this->nivelAcesso = 2;
        $this->conexao->beginTransaction();
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'password' => [Validador::OBRIGATORIO, Validador::TAMANHO_MINIMO(6)],
                'origem' => [Validador::ENUM('MS', 'ML', 'LP', Origem::MOBILE_ENTREGAS)],
            ]);

            CadastrosService::editPassword($this->conexao, $dadosJson['password'], $this->idUsuario);
            $usuario = UsuarioService::buscaDadosUsuarioParaAutenticacao(
                $this->conexao,
                $dadosJson['origem'],
                'ID_COLABORADOR',
                $this->idCliente
            );

            $this->resposta['id_colaborador'] = $usuario['id_colaborador'];

            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function editColaborador()
    {
        $this->nivelAcesso = 5;

        DB::beginTransaction();

        $dadosJson = Request::all();
        $dadosJson['telefone'] = Request::telefone();

        $idColaborador = Auth::user()->id_colaborador;
        $idUsuario = Auth::user()->id;

        $colaborador = ColaboradorModel::buscaInformacoesColaborador($idColaborador);

        if ($dadosJson['regime'] == 1) {
            Validador::validar($dadosJson, [
                'cnpj' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            $colaborador->cnpj = $dadosJson['cnpj'];
        } elseif ($dadosJson['regime'] == 2) {
            Validador::validar($dadosJson, [
                'cpf' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            $colaborador->cpf = $dadosJson['cpf'];
        } else {
            throw new BadRequestHttpException('Não foi possível identificar regime Mobile(3)');
        }

        $colaborador->razao_social = $dadosJson['razao_social'];

        if ($colaborador->telefone !== $dadosJson['telefone']) {
            $colaborador->telefone = $dadosJson['telefone'];
        }

        if ($colaborador->email !== $dadosJson['email']) {
            $colaborador->email = $dadosJson['email'];
        }

        if ($colaborador->regime != $dadosJson['regime']) {
            $colaborador->regime = $dadosJson['regime'];
        }

        $colaborador->update();

        $usuario = UsuarioService::buscaDadosUsuarioParaAutenticacao(
            DB::getPdo(),
            'MS',
            'ID_COLABORADOR',
            $idColaborador
        );
        if (!$usuario) {
            throw new BadRequestHttpException('Usuario ou senha estão incorretos.');
        }
        $usuario['token'] = RegrasAutenticacao::geraTokenPadrao(DB::getPdo(), $idUsuario);

        $enderecoPadrao = ColaboradorEndereco::buscaEnderecoPadraoColaborador();

        $usuario['Authorization'] = RegrasAutenticacao::geraAuthorization(
            $usuario['id'],
            $usuario['id_colaborador'],
            $usuario['nivel_acesso'],
            $usuario['permissao'],
            $usuario['nome'],
            $enderecoPadrao->uf,
            $usuario['regime']
        );

        DB::commit();

        return $usuario;
    }
    public function editPhoto()
    {
        $this->conexao->beginTransaction();
        try {
            Validador::validar($_FILES, [
                'foto_perfil' => [Validador::OBRIGATORIO, Validador::ARRAY],
            ]);
            extract($_FILES);
            $colaborador = ColaboradoresRepository::busca(['id' => $this->idCliente]);
            if ($foto_perfil['name'] === 'blob') {
                $foto_perfil['name'] = "{$foto_perfil['name']}";
            }
            if ($this->foto_perfil) {
                ColaboradoresRepository::deletaFotoS3($this->foto_perfil);
            }
            if ($url = ColaboradoresRepository::salvaFotoS3($foto_perfil, $this->idCliente)) {
                $colaborador->setFotoPerfil($url);
            } else {
                throw new Exception('Não foi possível salvar foto', 1);
            }
            ColaboradoresRepository::atualiza($colaborador);
            $this->conexao->commit();
            $this->retorno = ['status' => true, 'message' => $message, 'data' => $url];
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
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

    public function buscarDados()
    {
        $dadosCliente = ColaboradoresService::buscarDadosEndereco();

        if (!empty($dadosCliente['logradouro'])) {
            $enderecoCliente =
                $dadosCliente['logradouro'] .
                ' ' .
                $dadosCliente['numero'] .
                ' ' .
                $dadosCliente['bairro'] .
                ' - ' .
                $dadosCliente['cidade'] .
                ' (' .
                $dadosCliente['uf'] .
                ')';
        } else {
            $dadosCliente['esta_verificado'] = false;

            return $dadosCliente;
        }

        $entregadorProximo = PedidoItemMeuLookService::buscaTipoFreteMaisBaratoCarrinho('PM');
        $foraDoRaio = false;
        if (
            !isset($entregadorProximo['distancia']) ||
            (isset($entregadorProximo['distancia']) && $entregadorProximo['distancia'] > $entregadorProximo['raio'])
        ) {
            $foraDoRaio = true;
        }

        $dados = [
            'tipo_entrega' => $dadosCliente['tipo_entrega_padrao'],
            'id_tipo_entrega' => (int) ($dadosCliente['id_tipo_entrega_padrao'] ?? 0),
            'cidade' => $dadosCliente['cidade'],
            'uf' => $dadosCliente['uf'],
            'numero' => $dadosCliente['numero'],
            'endereco' => "{$dadosCliente['logradouro']}, {$dadosCliente['numero']} - {$dadosCliente['bairro']}",
            'endereco_completo' => $enderecoCliente,
            'fora_raio_entregador' => $foraDoRaio,
            'esta_verificado' => $dadosCliente['esta_verificado'],
            'id_cidade' => $dadosCliente['id_cidade'],
            'razao_social' => $dadosCliente['razao_social'],
            'telefone' => $dadosCliente['telefone'],
        ];

        return $dados;
    }

    public function verificaTelefoneErrado()
    {
        try {
            $this->retorno['data'] = ColaboradoresService::verificaTelefoneErrado($this->conexao, $this->idUsuario);
        } catch (\Throwable $e) {
            $this->retorno['message'] = $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaPontosRetirada(Origem $origem)
    {
        $idColaborador = Auth::user()->id_colaborador;
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'pesquisa' => [Validador::ENUM('LOCAL', 'PONTOS')],
            'id_produto' => [Validador::SE(Validador::OBRIGATORIO, Validador::NUMERO)],
            'latitude' => [Validador::SE(Validador::OBRIGATORIO, [Validador::LATITUDE])],
            'longitude' => [Validador::SE(Validador::OBRIGATORIO, [Validador::LONGITUDE])],
        ]);

        $colaborador = ColaboradoresService::consultaDadosColaborador($idColaborador);
        if (isset($dadosJson['latitude'], $dadosJson['longitude'])) {
            $colaborador['cidade']['latitude'] = (float) $dadosJson['latitude'];
            $colaborador['cidade']['longitude'] = (float) $dadosJson['longitude'];
        }

        $produtos = [];
        if (!empty($dadosJson['id_produto'])) {
            $produtos[] = (int) $dadosJson['id_produto'];
        } elseif ($origem->ehMl()) {
            $produtos = PedidoItemMeuLookService::consultaProdutosCarrinho(false);
            $produtos = array_column($produtos['carrinho'], 'uuid');
        } else {
            $transacao = app(TransacaoFinanceiraService::class);
            $transacao->pagador = $idColaborador;
            $transacao->buscaTransacaoCR(DB::getPdo());
            if (!empty($transacao->id)) {
                $produtos = TransacaoFinanceiraItemProdutoService::buscaDadosProdutosTransacao(
                    DB::getPdo(),
                    $transacao->id,
                    $idColaborador
                );
                $produtos = array_column($produtos, 'uuid_produto');
            }
        }

        $pontosRetirada = IBGEService::buscaPontosRetiradaDisponiveis(
            $dadosJson['pesquisa'],
            $produtos,
            $origem,
            Arr::only($colaborador['cidade'], ['latitude', 'longitude'])
        );

        return [
            'cidade' => $colaborador['cidade'],
            'pontos' => $pontosRetirada,
        ];
    }

    public function atualizaLocalizacao()
    {
        DB::beginTransaction();

        $dadosJson = Request::all();

        Validador::validar($dadosJson, [
            'latitude' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'longitude' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        TipoFreteService::salvaGeolocalizacao($dadosJson['latitude'], $dadosJson['longitude']);

        DB::commit();
    }
}
