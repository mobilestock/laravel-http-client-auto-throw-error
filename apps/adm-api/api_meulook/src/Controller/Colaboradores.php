<?php

namespace api_meulook\Controller;

use api_meulook\Models\Request_m;
use Error;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorDocumento;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\TransportadoresRaio;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\repository\FotosRepository;
use MobileStock\repository\UsuariosRepository;
use MobileStock\service\AvaliacaoTipoFreteService;
use MobileStock\service\Cadastros\CadastrosService;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\IBGEService;
use MobileStock\service\Lancamento\LancamentoConsultas;
use MobileStock\service\TipoFreteService;
use MobileStock\service\TransporteService;
use MobileStock\service\UsuarioService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Colaboradores extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    public function seTornarPonto(TipoFreteService $tipoFrete)
    {
        DB::beginTransaction();
        $dadosJson = Request::all();
        $dadosJson = array_merge(json_decode($dadosJson['dados'], true), $dadosJson);

        Validador::validar($dadosJson, [
            'endereco' => [Validador::OBRIGATORIO],
            'bairro' => [Validador::OBRIGATORIO],
            'numero' => [Validador::OBRIGATORIO],
            'complemento' => [],
            'nome_ponto' => [Validador::OBRIGATORIO],
            'horarios_entrega' => [Validador::OBRIGATORIO],
            'latitude' => [Validador::OBRIGATORIO, Validador::LATITUDE],
            'longitude' => [Validador::OBRIGATORIO, Validador::LONGITUDE],
        ]);

        $idUsuario = Auth::user()->id;
        $idColaborador = Auth::user()->id_colaborador;
        $existeTipoFrete = TipoFreteService::existePonto();
        if ($existeTipoFrete) {
            throw new ConflictHttpException('Já possui requisição para ser ponto.');
        }

        $usuarioPossuiSenha = UsuarioService::usuarioPossuiSenha();
        if (!$usuarioPossuiSenha) {
            Validador::validar($dadosJson, [
                'senha' => [Validador::OBRIGATORIO, Validador::TAMANHO_MINIMO(6)],
            ]);

            CadastrosService::editPassword(DB::getPdo(), $dadosJson['senha'], $idUsuario);
        }

        $colaborador = ColaboradorModel::buscaInformacoesColaborador($idColaborador);
        if (empty($colaborador->email) || empty($colaborador->nome_instagram)) {
            if (empty($colaborador->email)) {
                Validador::validar($dadosJson, [
                    'email' => [Validador::OBRIGATORIO, Validador::EMAIL],
                ]);

                $colaborador->email = $dadosJson['email'];
            }
            if (empty($colaborador->nome_instagram)) {
                $colaborador->nome_instagram = $dadosJson['instagram'] ?: null;
            }

            $colaborador->update();
        }

        $colaboradorEndereco = ColaboradorEndereco::buscaEnderecoPadraoColaborador();
        $informacoesCidade = IBGEService::buscarInfoCidade($colaboradorEndereco->id_cidade);

        if (empty($informacoesCidade)) {
            throw new NotFoundHttpException('Cidade inválida');
        }

        $colaboradoresDocumentos = new ColaboradorDocumento();
        $colaboradoresDocumentos->id_colaborador = $idColaborador;
        $colaboradoresDocumentos->url_documento = FotosRepository::salvarFotoAwsS3(
            $_FILES['foto_comprovante'],
            "FOTO_COMPROVANTE_ENDERECO_{$_ENV['AMBIENTE']}_{$idColaborador}_" . rand(),
            'ARQUIVOS_PRIVADOS',
            true
        );
        $colaboradoresDocumentos->tipo_documento = 'COMPROVANTE_ENDERECO';
        $colaboradoresDocumentos->save();

        $colaboradoresDocumentos = new ColaboradorDocumento();
        $colaboradoresDocumentos->id_colaborador = $idColaborador;
        $colaboradoresDocumentos->url_documento = FotosRepository::salvarFotoAwsS3(
            $_FILES['foto_documento'],
            "FOTO_CEDULA_IDENTIDADE_{$_ENV['AMBIENTE']}_{$idColaborador}_" . rand(),
            'ARQUIVOS_PRIVADOS',
            true
        );
        $colaboradoresDocumentos->tipo_documento = 'CEDULA_IDENTIDADE';
        $colaboradoresDocumentos->save();

        $tipoFrete->id_usuario = $idUsuario;
        $tipoFrete->id_colaborador = $idColaborador;
        $tipoFrete->tipo_ponto = 'PP';
        $tipoFrete->categoria = 'PE';
        $tipoFrete->latitude = $dadosJson['latitude'];
        $tipoFrete->longitude = $dadosJson['longitude'];
        $tipoFrete->foto = $colaborador->foto_perfil;
        $tipoFrete->nome = $dadosJson['nome_ponto'];
        $tipoFrete->mensagem = "{$dadosJson['endereco']}, {$dadosJson['numero']} {$dadosJson['complemento']}, {$dadosJson['bairro']}";
        $tipoFrete->mensagem_cliente = 'Seu pedido chegará em breve no ponto de retirada.
                Enviaremos uma mensagem via Whatsapp quando a mercadoria chegar ao destino.';
        $tipoFrete->titulo = Str::lower(
            Str::replace(' ', '_', "retirar_em_{$informacoesCidade['nome']}_{$informacoesCidade['uf']}_$idColaborador")
        );
        $tipoFrete->horario_de_funcionamento = $dadosJson['horarios_entrega'];
        $tipoFrete->salva(DB::getPdo());

        DB::commit();
    }

    public function buscaEnderecoDeEntrega()
    {
        $endereco = ColaboradorEndereco::buscaEnderecoPadraoColaborador();
        if (empty(implode('', Arr::only($endereco->toArray(), ['logradouro', 'numero', 'bairro'])))) {
            throw new InvalidArgumentException('Endereço de entrega não encontrado');
        }

        $enderecoCliente = "{$endereco->logradouro} {$endereco->numero}, {$endereco->bairro}";
        $enderecoCliente .= " {$endereco->cidade} ({$endereco->uf})";

        return $enderecoCliente;
    }

    public function verificaEnderecoDigitado()
    {
        try {
            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);

            Validador::validar($dadosJson, [
                'endereco' => [Validador::OBRIGATORIO],
            ]);

            $dadosEnderecoCliente = IBGEService::buscaDadosEnderecoApiGoogle($dadosJson['endereco']);

            $statusRetorno = $dadosEnderecoCliente['status'];
            if (!$statusRetorno || !in_array($statusRetorno, ['ZERO_RESULTS', 'OK'])) {
                throw new Exception("Erro interno ao verificar endereço! ({$statusRetorno})");
            }

            $enderecos = [];
            foreach ($dadosEnderecoCliente['results'] as $resultado) {
                if (count(array_intersect(['street_address', 'route', 'premise'], $resultado['types']))) {
                    $enderecos[] = $resultado;
                }
            }

            $this->retorno['data']['enderecos'] = $enderecos;

            $this->codigoRetorno = 200;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Consulta efetuada com sucesso.';
        } catch (\Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function editaCadastro()
    {
        DB::beginTransaction();
        $dadosJson = Request::all();
        $dadosJson['telefone'] = Request::telefone();
        Validador::validar($dadosJson, [
            'nome' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            'usuario_meulook' => [Validador::OBRIGATORIO],
            'email' => [Validador::OBRIGATORIO, Validador::EMAIL],
        ]);

        $idColaborador = Auth::user()->id_colaborador;
        $colaborador = ColaboradorModel::buscaInformacoesColaborador($idColaborador);
        if ($colaborador->telefone !== $dadosJson['telefone']) {
            $existeTelefone = ColaboradorModel::existeTelefone($dadosJson['telefone']);
            if ($existeTelefone) {
                throw new ConflictHttpException('Telefone já está sendo utilizado');
            }

            $colaborador->telefone = $dadosJson['telefone'];
        }
        if ($colaborador->usuario_meulook !== $dadosJson['usuario_meulook']) {
            $colaborador->validaNomeUsuarioMeuLook($dadosJson['usuario_meulook']);
            $existeUsuario = ColaboradorModel::existeUsuarioMeulook($dadosJson['usuario_meulook']);
            if ($existeUsuario) {
                throw new ConflictHttpException('Já existe usuário com esse nome');
            }

            $colaborador->usuario_meulook = $dadosJson['usuario_meulook'];
        }
        if (isset($_FILES['foto_perfil'])) {
            $fotoAtual = $colaborador->foto_perfil;
            if ($fotoAtual) {
                ColaboradoresRepository::deletaFotoS3($fotoAtual);
            }

            $foto = ColaboradoresRepository::salvaFotoS3($_FILES['foto_perfil'], $idColaborador);
            $colaborador->foto_perfil = $foto;
        }
        // Coisas de ponto
        if (isset($dadosJson['horario_de_funcionamento'])) {
            $idTipoFrete = TipoFreteService::buscaIdTipoFrete($idColaborador);

            $tipoFreteService = app(TipoFreteService::class);
            $tipoFreteService->id = $idTipoFrete;
            $tipoFreteService->horario_de_funcionamento = $dadosJson['horario_de_funcionamento'];
            $tipoFreteService->nome = $dadosJson['nome_ponto'];
            $tipoFreteService->salva(DB::getPdo());
        }

        $colaborador->razao_social = $dadosJson['nome'];
        $colaborador->email = $dadosJson['email'];
        $colaborador->update();
        $informacoesColaborador = ColaboradoresService::consultaDadosColaborador($idColaborador);
        DB::commit();

        return $informacoesColaborador;
    }

    public function preencherDadosColaborador()
    {
        try {
            $this->conexao->beginTransaction();

            $dadosJson = $this->request->request->all();

            Validador::validar($dadosJson, [
                'endereco' => [Validador::OBRIGATORIO, Validador::SANIZAR],
                // 'bairro' => [Validador::OBRIGATORIO, Validador::SANIZAR],
                'numero' => [Validador::OBRIGATORIO],
                'complemento' => [Validador::SANIZAR],
                'ponto_de_referencia' => [Validador::SANIZAR],
            ]);

            $colaborador = ColaboradoresRepository::busca(['id' => $this->idCliente], 'LIMIT 1', $this->conexao);

            $colaborador->setEndereco($dadosJson['endereco']);
            // $colaborador->setBairro($dadosJson['bairro']);
            $colaborador->setNumero($dadosJson['numero']);
            $colaborador->setComplemento($dadosJson['complemento']);
            $colaborador->setPonto_de_referencia($dadosJson['ponto_de_referencia']);

            if (isset($dadosJson['cep'])) {
                Validador::validar($dadosJson, ['cep' => [Validador::SANIZAR]]);
                $colaborador->setCep($dadosJson['cep']);
            }
            if (isset($dadosJson['cpf'])) {
                Validador::validar($dadosJson, ['cpf' => [Validador::CPF]]);
                $colaborador->setCpf($dadosJson['cpf']);
                $colaborador->setRegime(2);
            }
            if (isset($dadosJson['cnpj'])) {
                Validador::validar($dadosJson, ['cnpj' => [Validador::CNPJ]]);
                $colaborador->setCnpj($dadosJson['cnpj']);
                $colaborador->setRegime(1);
            }

            ColaboradoresRepository::atualiza($colaborador, [], $this->conexao);

            $this->conexao->commit();
            $this->retorno['message'] = 'Dados atualizados!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
            $this->codigoRetorno = 400;
            $this->conexao->rollback();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaCadastro()
    {
        $informacoesColaborador = ColaboradoresService::consultaDadosColaborador(Auth::user()->id_colaborador);
        $informacoesColaborador['situacao_cadastro_ponto'] = TipoFreteService::categoriaTipoFrete();

        return $informacoesColaborador;
    }

    public function buscaSaldo()
    {
        $dados = ColaboradoresRepository::consultaSaldoColaboradorMeuLook();

        return $dados;
    }

    public function buscaSaldoEmDetalhe()
    {
        $geral = Request::boolean('geral');
        $idColaborador = Auth::user()->id_colaborador;
        $totalVendasEntregues = 0;

        $dadosVendasEntregues = LancamentoConsultas::buscaLancamentosFuturos($idColaborador, $geral);

        foreach ($dadosVendasEntregues as $venda) {
            $totalVendasEntregues += $venda['valor'];
        }

        $totalVendasNaoEntregues = LancamentoConsultas::buscaValorTotalLancamentosPendentes($idColaborador);

        $saldoBloqueado = LancamentoConsultas::buscaSaldoBloqueadoCliente($idColaborador);

        $nomesArrayItems = [
            'total_vendas_entregues' => round($totalVendasEntregues, 2),
            'total_vendas_nao_entregues' => round($totalVendasNaoEntregues, 2),
            'saldo_bloqueado' => round($saldoBloqueado, 2),
        ];

        return $nomesArrayItems;
    }
    public function atualizarMetodoEnvioPadrao(int $idTipoFrete)
    {
        DB::beginTransaction();

        $colaborador = ColaboradorModel::buscaInformacoesColaborador(Auth::user()->id_colaborador);
        $colaborador->id_tipo_entrega_padrao = $idTipoFrete;
        $colaborador->update();

        DB::commit();
    }
    public function pontoSelecionadoPraTransacao(int $idTransacao)
    {
        $pontoSelecionado = IBGEService::buscaPontoSelecionado($idTransacao);

        return $pontoSelecionado;
    }

    public function buscaNomeUsuario()
    {
        try {
            $nomeUsuario = ColaboradoresRepository::buscaNomeUsuarioMeuLook($this->conexao, $this->idCliente);
            $this->resposta = $nomeUsuario;
        } catch (\Throwable $e) {
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaPermissao()
    {
        try {
            $permissoes = ColaboradoresRepository::buscaPermissaoUsuario($this->conexao, $this->idCliente);
            $this->resposta = $permissoes;
        } catch (\Throwable $e) {
            $this->resposta['message'] = $e->getMessage();
            $this->codigoRetorno = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $this->respostaJson
                ->setData($this->resposta)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    // public function buscaRecomendacoesInfluencers()
    // {
    //     try {
    //         $this->retorno['data'] = ColaboradoresService::buscaInfluencersRecomendadosMeuLook($this->conexao, $this->idCliente);
    //         $this->retorno['message'] = 'Influencers recomendados buscados com sucesso';
    //         $this->status = 200;
    //     } catch (\Throwable $e) {
    //         $this->retorno['status'] = false;
    //         $this->retorno['message'] = $e->getMessage();
    //         $this->status = 400;
    //     } finally {
    //         $this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
    //         exit;
    //     }
    // }

    public function vendasAbertoPonto()
    {
        $categoriaPonto = TipoFreteService::categoriaTipoFrete();
        if (in_array($categoriaPonto, ['ML', 'PE'])) {
            return [
                'produtos' => TipoFreteService::buscaProdutosAbertoPonto('pagos'),
                'produtosConferidos' => TipoFreteService::buscaProdutosAbertoPonto('conferidos'),
                'produtosAguardandoColeta' => TipoFreteService::buscaProdutosCaminhoPonto(true),
                'produtosCaminho' => TipoFreteService::buscaProdutosCaminhoPonto(false),
                'produtosEntregues' => TipoFreteService::buscaUltimaEntregaPonto(),
                'categoriaPonto' => $categoriaPonto,
                'dadosUsuario' => null,
                'emAndamento' => TransporteService::buscaDadosParaControlePontoColeta('AB'),
                'emTransporte' => TransporteService::buscaDadosParaControlePontoColeta('EX'),
                'comEntregador' => TransporteService::buscaDadosParaControlePontoColeta('PT'),
            ];
        } else {
            return [
                'produtos' => [],
                'produtosCaminho' => [],
                'produtosEntregues' => [],
                'categoriaPonto' => $categoriaPonto != null ? 'PE' : null,
                'dadosUsuario' => $categoriaPonto == null ? ColaboradoresService::buscaDadosParaTornarPonto() : null,
            ];
        }
    }

    public function buscaConsumidoresPonto()
    {
        try {
            $dadosGet = $this->request->query->all();
            Validador::validar($dadosGet, ['busca' => [Validador::NAO_NULO]]);
            $this->retorno['data'] = TipoFreteService::buscaConsumidores(
                $this->conexao,
                $this->idCliente,
                $dadosGet['busca']
            );
            $this->retorno['message'] = 'Consumidores buscados com sucesso!';
            $this->status = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    public function buscaHistoricoConsumidor(array $dados)
    {
        try {
            $this->retorno['data'] = TipoFreteService::buscaHistoricoConsumidor(
                $this->conexao,
                $dados['id'],
                $this->idCliente
            );
            $this->retorno['message'] = 'Histórico carregado com sucesso';
            $this->status = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }

    public function preencheAutenticacao()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);

            Validador::validar($dadosJson, [
                'email' => [Validador::OBRIGATORIO, Validador::EMAIL],
                'senha' => [Validador::OBRIGATORIO],
            ]);

            UsuariosRepository::atualizaAutenticacaoUsuario(
                $this->conexao,
                $this->idUsuario,
                $dadosJson['senha'],
                $dadosJson['email']
            );

            $this->conexao->commit();
            $this->status = 200;
            // $this->retorno['data']['authorization'] = JWT::encode($dadosUsuario, Globals::JWT_KEY);
            // $this->retorno['data']['token'] = $usuario['token'];
        } catch (\PDOException $pdoException) {
            $this->conexao->rollBack();
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $pdoException->getMessage();

            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
        } catch (\Throwable $ex) {
            $this->conexao->rollback();
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

    public function validarPosicaoPonto()
    {
        try {
            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);

            Validador::validar($dadosJson, [
                'latitude' => [Validador::OBRIGATORIO],
                'longitude' => [Validador::OBRIGATORIO],
            ]);

            $pontoProximo = TipoFreteService::validarPosicaoPonto(
                $this->conexao,
                $dadosJson['latitude'],
                $dadosJson['longitude']
            );

            $pontoValidado = $pontoProximo['distancia'] > 1;

            $this->retorno['data'] = [
                'ponto_proximo' => $pontoProximo,
                'validado' => $pontoValidado,
            ];

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
        }
    }

    public function bloqueiaColaboradorPostar($cliente)
    {
        try {
            Validador::validar($cliente, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            if (UsuarioService::verificaSeEstaBloqueado($this->conexao, $cliente['id']) === 'T') {
                throw new Exception('Usuário já está bloqueado');
            }
            UsuarioService::bloqueiaDeCriarLook($this->conexao, $cliente['id']);
            $this->retorno['message'] = 'Colaborador bloqueado com sucesso';
            $this->status = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function desbloqueiaColaboradorPostar($cliente)
    {
        try {
            Validador::validar($cliente, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            if (UsuarioService::verificaSeEstaBloqueado($this->conexao, $cliente['id']) === 'F') {
                throw new Exception('Usuário já está desbloqueado');
            }
            UsuarioService::desbloqueiaDeCriarLook($this->conexao, $cliente['id']);
            $this->retorno['message'] = 'Colaborador desbloqueado com sucesso';
            $this->status = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function verificaSeBloqueado()
    {
        try {
            $this->retorno['data'] = UsuarioService::verificaSeEstaBloqueado($this->conexao, $this->idCliente);
            $this->retorno['message'] = 'Verificado com sucesso';
            $this->status = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function avaliar()
    {
        try {
            $this->conexao->beginTransaction();
            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nota_atendimento' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'nota_localizacao' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $jaAvaliou = AvaliacaoTipoFreteService::buscaAvaliacaoColaborador(
                $this->conexao,
                $this->idCliente,
                $dadosJson['id_ponto']
            );

            if (!$jaAvaliou) {
                throw new Error('Não pôde criar o registro de avaliação');
            } elseif ($jaAvaliou && $jaAvaliou['nota_atendimento'] != 0 && $jaAvaliou['nota_localizacao'] != 0) {
                throw new Error('Já avaliou esse ponto!');
            }

            $avaliacaoService = new AvaliacaoTipoFreteService();
            $avaliacaoService->id_tipo_frete = $dadosJson['id_ponto'];
            $avaliacaoService->id_colaborador = $this->idCliente;
            $avaliacaoService->nota_atendimento = $dadosJson['nota_atendimento'];
            $avaliacaoService->nota_localizacao = $dadosJson['nota_localizacao'];
            $avaliacaoService->comentario = $dadosJson['comentario'] ?? '';
            $avaliacaoService->atualizar($this->conexao, $jaAvaliou['id']);

            $this->retorno['data'] = [];
            $this->retorno['message'] = 'Ponto avaliado com sucesso!';
            $this->status = 200;
            $this->conexao->commit();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function avaliacoesConsumidor()
    {
        $avaliacaoPendente = AvaliacaoTipoFreteService::buscaAvaliacaoPendenteColaborador();

        return $avaliacaoPendente;
    }
    public function adiarAvaliacao()
    {
        try {
            Validador::validar(['json' => $this->json], ['json' => [Validador::JSON]]);
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, ['id_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO]]);
            $this->retorno['data'] = AvaliacaoTipoFreteService::adiarAvaliacao(
                $this->conexao,
                $this->idCliente,
                $dadosJson['id_ponto']
            );
            $this->retorno['message'] = 'Avaliação adiada com sucesso!';
            $this->status = 200;
        } catch (\Throwable $e) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->status = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function atualizaTelefone()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);
            $dadosJson['telefone'] = preg_replace('/[^0-9]+/', '', $dadosJson['telefone']);
            Validador::validar($dadosJson, [
                'telefone' => [Validador::OBRIGATORIO, Validador::TELEFONE],
            ]);

            if (mb_strpos($dadosJson['telefone'], '0') === 0) {
                throw new Exception('O número de telefone não pode começar com 0');
            }

            ColaboradoresRepository::atualizaNumeroTelefone($this->conexao, $this->idCliente, $dadosJson['telefone']);

            $this->conexao->commit();
            $this->retorno['message'] = 'Número de telefone atualizado com sucesso!';
            $this->codigoRetorno = 200;
        } catch (\Throwable $th) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $th->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaSituacaoPonto()
    {
        try {
            $this->retorno['data'] = TipoFreteService::buscaSituacaoPonto($this->conexao, $this->idCliente);
            $this->retorno['message'] = 'Situação encontrada com sucesso!';
            $this->codigoRetorno = 200;
        } catch (\Throwable $th) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = $th->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscarColaboradoresParaColetaMobileEntregas()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'pesquisa' => [Validador::OBRIGATORIO],
        ]);

        $dados['pesquisa'] = preg_match('/^[0-9\s\-\(\)]+$/', $dados['pesquisa'])
            ? preg_replace('/[^0-9]/', '', $dados['pesquisa'])
            : $dados['pesquisa'];

        $colaboradores = ColaboradoresService::buscarColaboradoresParaColetaMobileEntregas($dados['pesquisa']);

        foreach ($colaboradores as $key => $colaborador) {
            $colaboradores[$key]['valor_coleta'] = TransportadoresRaio::buscaEntregadoresMobileEntregas(
                $colaborador['id_endereco']
            )['valor_coleta'];
        }

        return $colaboradores;
    }
}
