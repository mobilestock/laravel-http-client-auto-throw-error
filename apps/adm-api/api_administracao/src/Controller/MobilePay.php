<?php

namespace api_administracao\Controller;

use api_administracao\Models\Cadastro;
use api_administracao\Models\MobilePay as Pay;
use api_administracao\Models\Request_m;
use api_estoque\Cript\Cript;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ContaBancaria;
use MobileStock\model\Lancamento;
use MobileStock\model\Origem;
use MobileStock\model\Taxas;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\Adiantamento\EmprestimoService;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\ConfiguracaoService;
use MobileStock\service\CreditosDebitosService;
use MobileStock\service\Email;
use MobileStock\service\FAQService;
use MobileStock\service\IuguService\IuguServiceConta;
use MobileStock\service\Lancamento\LancamentoConsultas;
use MobileStock\service\Lancamento\LancamentoCrud;
use MobileStock\service\PrioridadePagamento\PrioridadePagamentoService;
use MobileStock\service\TransacaoFinanceira\TransacaoConsultasService;
use MobileStock\service\UsuarioService;
use MobileStock\service\ZoopTokenContaBancariaService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MobilePay extends Request_m
{
    public function __construct()
    {
        //$this->nivelAcesso = '1';
        parent::__construct();
        $this->respostaJson = new JsonResponse();
        $this->conexao = app(\PDO::class);
    }

    public function buscaExtrato()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            Validador::validar(
                ['pagina' => $this->request->get('pagina')],
                [
                    'pagina' => [Validador::OBRIGATORIO, Validador::NUMERO],
                ]
            );

            $pagina = (int) $this->request->get('pagina');

            $array = json_decode($this->json, true);
            extract($array);
            if (isset($type)) {
                switch ($type) {
                    case 'days':
                        $inicio = $de;
                        $fim = $ate;
                        break;
                    case 'weeks':
                        $inicio = date('Y-m-d', strtotime('-15 days'));
                        $fim = date('Y-m-d');
                        break;
                    case 'week':
                        $inicio = date('Y-m-d', strtotime('-7 days'));
                        $fim = date('Y-m-d');
                        break;
                    case 'month':
                        $inicio = date('Y-m-01');
                        $fim = date('Y-m-t');
                        break;
                    case '30days':
                        $inicio = '';
                        $fim = '';
                        break;
                    case 'today':
                        $inicio = date('Y-m-d');
                        $fim = date('Y-m-d');
                        break;
                }
            } else {
                $inicio = date('Y-m-d');
                $fim = date('Y-m-d');
            }

            $data['lancamentos'] = LancamentoConsultas::buscaCreditosPorClientePay(
                $this->conexao,
                $this->idCliente,
                $total,
                $inicio,
                $fim,
                $pagina
            );
            $data['valor_total'] = LancamentoConsultas::consultaCreditoCliente($this->conexao, $this->idCliente);
            //$data['saldos'] = LancamentoConsultas::consultaSitucaoSaldoCliente($this->conexao, $this->idCliente);
            $data['lancamentos'] = LancamentoConsultas::buscaRecebivelLancamento($this->conexao, $data['lancamentos']);

            $data['lancamentos'] = array_map(function ($lancamento) {
                if (isset($lancamento['imagens'])) {
                    $lancamento['imagens'] = explode(',', $lancamento['imagens']);
                }
                return $lancamento;
            }, $data['lancamentos']);

            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Crédito Encontrado com Sucesso', 'data' => $data])
                ->setStatusCode(201)
                ->send();
        } catch (\Throwable $exception) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $exception->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }
    public function buscaContato()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $array = json_decode($this->json, true);
            $data['contato'] = Pay::buscaContato($this->conexao, $array['razao_social'], $this->idCliente);
            $data['pay'] = Pay::buscaContatoPay($this->conexao, $array['razao_social'], $this->idCliente);
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Crédito Encontrado com Sucesso', 'data' => $data])
                ->setStatusCode(201)
                ->send();
        } catch (\Throwable $exception) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $exception->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }
    public function saldo()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $array = json_decode($this->json, true);
            extract($array);
            $data['valor_total'] = LancamentoConsultas::consultaCreditoCliente($this->conexao, $this->idCliente);
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Crédito Encontrado com Sucesso', 'data' => $data])
                ->setStatusCode(201)
                ->send();
        } catch (\Throwable $exception) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $exception->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }
    public function buscaHistoryContatos()
    {
        try {
            $lista = LancamentoConsultas::buscaHistoricoTransferencia($this->conexao, $this->idCliente);
            $data = Pay::buscaHistorico($this->conexao, $lista, $this->idCliente);
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Busca Realizada com Sucesso', 'data' => $data])
                ->setStatusCode(201)
                ->send();
        } catch (\Throwable $exception) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $exception->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function verifyPassword()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'password' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            if ($pass_confirm = Pay::buscaPassword($this->conexao, $this->idUsuario)) {
                $password = sha1($password);
                if ($pass_confirm != $password) {
                    throw new Exception('Senha Inválida', 1);
                }
            } else {
                throw new Exception('Senha Não Encontrada', 1);
            }

            $this->respostaJson->setData(['status' => true, 'message' => 'Senha Confirmada', 'data' => []])->send();
        } catch (\Throwable $e) {
            $data = '';

            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function paymentTransfer()
    {
        DB::beginTransaction();
        $serviceColaboradores = new ColaboradoresService();
        $serviceColaboradores->id = Auth::user()->id_colaborador;
        $serviceColaboradores->buscaSituacaoFraude(DB::getPdo(), ['DEVOLUCAO', 'CARTAO']);
        $situacoesFraude = (array) $serviceColaboradores->situacao_fraude;
        foreach ($situacoesFraude as $situacaoFraude) {
            if (in_array($situacaoFraude['situacao'], ['PE', 'FR'])) {
                throw new UnauthorizedHttpException('', 'Erro ao realizar transferência. (Usuário em análise)');
            }
        }

        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'g-recaptcha-response' => [Validador::SE(App::isProduction(), Validador::OBRIGATORIO)],
            'password' => [Validador::OBRIGATORIO],
            'transact_value' => [Validador::OBRIGATORIO],
            'type' => [Validador::OBRIGATORIO],
            'to' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);
        extract($dadosJson);

        if (App::isProduction()) {
            $resposta = Http::get('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => env('RECAPTCHA_SECRET'),
                'response' => $dadosJson['g-recaptcha-response'],
            ])->json();

            if (empty($resposta) || !$resposta['success']) {
                throw new BadRequestHttpException('Por favor, realize a verificação do reCAPTCHA corretamente!');
            }
        }

        $password = base64_decode($password);
        if ($pass_confirm = Pay::buscaPassword(DB::getPdo(), Auth::id())) {
            $password = sha1($password);
            if ($pass_confirm != $password) {
                throw new UnauthorizedHttpException('', 'Senha Inválida');
            } else {
                if ($total = LancamentoConsultas::consultaCreditoCliente(DB::getPdo(), Auth::user()->id_colaborador)) {
                    if ((float) $total >= (float) $transact_value) {
                        if ($type == 'MP' && ($colaborador = ColaboradoresRepository::busca(['id' => $to]))) {
                            $pagador = ColaboradoresRepository::busca(['id' => Auth::user()->id_colaborador]);
                            $user = $pagador->extrair();
                            $lancamento_direito = new Lancamento(
                                'P',
                                1,
                                'RI',
                                $to,
                                date('Y-m-d  H:i:s'),
                                (float) $transact_value,
                                Auth::id(),
                                1
                            );
                            $lancamento_direito->id_pagador = Auth::user()->id_colaborador;
                            $lancamento_direito->id_recebedor = $to;
                            $lancamento_direito->documento = 7;
                            $lancamento_direito->documento_pagamento = 7;
                            $lancamento_direito->observacao = $user['razao_social'];
                            $direito = LancamentoCrud::salva(DB::getPdo(), $lancamento_direito);

                            $lancamento_dever = new Lancamento(
                                'R',
                                1,
                                'PI',
                                Auth::user()->id_colaborador,
                                date('Y-m-d  H:i:s'),
                                (float) $transact_value,
                                Auth::id(),
                                1
                            );
                            $lancamento_dever->id_pagador = $to;
                            $lancamento_dever->id_recebedor = Auth::user()->id_colaborador;
                            $lancamento_dever->documento = 7;
                            $lancamento_dever->documento_pagamento = 7;
                            $lancamento_dever->lancamento_origem = $direito->id;
                            $nome = $colaborador->extrair();
                            $lancamento_dever->observacao = $nome['razao_social'];
                            $debito = LancamentoCrud::salva(DB::getPdo(), $lancamento_dever);
                            $id_saque = 0;
                            $total = LancamentoConsultas::consultaCreditoCliente(
                                DB::getPdo(),
                                Auth::user()->id_colaborador
                            );
                            $data = [
                                'transfer_id' => $debito->id,
                                'transfer' => $id_saque,
                                'payment' => (float) $transact_value,
                                'credit' => (float) $total,
                                'to' => $to,
                                'from' => Auth::user()->id_colaborador,
                            ];
                        } elseif ($type == 'MP') {
                            throw new Exception('Destinatário Não Encontrado.', 1);
                        } elseif ($type == 'CB') {
                            //!CadastrosService::existeUserID($this->conexao, $to)
                            $prioridade = new PrioridadePagamentoService();
                            $prioridade->id_colaborador = Auth::user()->id_colaborador;
                            $prioridade->id_conta_bancaria = $to;
                            $prioridade->valor_pagamento = (float) $transact_value;
                            $prioridade->criaPrioridadePagamento(DB::getPdo());
                            $data = [
                                'transfer_id' => $prioridade->id,
                                'transfer' => $prioridade->id,
                                'payment' => (float) $transact_value,
                                'credit' => (float) $total,
                                'to' => $to,
                                'from' => Auth::user()->id_colaborador,
                            ];
                        }
                    } else {
                        throw new Exception('Valor Inválido! Maior que o saldo total.', 1);
                    }
                } else {
                    throw new Exception('Saldo total negativo ou zerado.', 1);
                }
            }
        } else {
            throw new Exception('Senha Não Encontrada', 1);
        }
        DB::commit();

        return $data;
    }
    /**
     * FUNÇÃO DE SAQUE DO LOOK PAY
     * WITHDRAW -> RETIRA-O (VERBO BANCÁRIO EM INGLÊS)
     */
    public function withdraw()
    {
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
                'password' => [Validador::OBRIGATORIO],
                'transact_value' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            if ($pass_confirm = Pay::buscaPassword($this->conexao, $this->idUsuario)) {
                $password = sha1($password);
                if ($pass_confirm != $password) {
                    throw new Exception('Senha Inválida', 1);
                } else {
                    if ($total = LancamentoConsultas::consultaCreditoCliente($this->conexao, $this->idCliente)) {
                        if ((float) $total >= (float) $transact_value) {
                            if ($idZoopRemetente = Cadastro::buscaIdIugu($this->conexao, $this->idCliente)) {
                                //$idZoopRemetente = Cadastro::buscaIdZoop($this->conexao, $this->idCliente)
                                // $lancamento_dever = new Lancamento('R', 1, 'SI', $this->idCliente, date('Y-m-d  H:i:s'), floatVal($transact_value), $this->idUsuario, 1);
                                // $lancamento_dever->id_recebedor = $this->idCliente;
                                // $lancamento_dever->documento = 7;
                                // $lancamento_dever->documento_pagamento = 7;
                                // $debito = LancamentoCrud::salva($this->conexao, $lancamento_dever);

                                $prioridade = new PrioridadePagamentoService();
                                $prioridade->id_colaborador = $this->idCliente;
                                $prioridade->valor_pagamento = (float) $transact_value;
                                $prioridade->criaPrioridadePagamento($this->conexao);

                                $total = LancamentoConsultas::consultaCreditoCliente($this->conexao, $this->idCliente);
                                $data = [
                                    'transfer_id' => $prioridade->id,
                                    'transfer' => '0',
                                    'payment' => (float) $transact_value,
                                    'credit' => (float) $total,
                                    'from' => $this->idCliente,
                                    'to' => $this->idCliente,
                                ];
                            } else {
                                throw new Exception('Remetente Não Possui Cadastro no Look Pay.', 1);
                            }
                        } else {
                            throw new Exception('Valor Inválido! Maior que o saldo total.', 1);
                        }
                    } else {
                        throw new Exception('Saldo total negativo ou zerado.', 1);
                    }
                }
            } else {
                throw new Exception('Senha Não Encontrada', 1);
            }
            $this->conexao->commit();
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Saque Efetuado com Sucesso!', 'data' => $data])
                ->send();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function validationValue()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'transact_value' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            if ($total = LancamentoConsultas::consultaCreditoCliente($this->conexao, $this->idCliente)) {
                if ((float) $total >= (float) $transact_value && (float) $transact_value > 0) {
                    $message = 'Transação Validada';
                } else {
                    throw new Exception('Valor Inválido! Maior que o saldo total.', 1);
                }
            } else {
                throw new Exception('Saldo total negativo ou zerado.', 1);
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

    public function hasPassword()
    {
        try {
            if (Pay::buscaPassword($this->conexao, $this->idUsuario)) {
                $message = 'Success';
                $data = ['has' => 1];
            } else {
                $message = 'Erro';
                $data = ['has' => 0];
            }
            $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => $data])->send();
        } catch (\Throwable $e) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function sendToken()
    {
        try {
            $colaborador = ColaboradoresRepository::busca(['id' => $this->idCliente]);

            if (empty($colaborador)) {
                throw new Exception('Colaborador não encontrado');
            }

            $email = $colaborador->getEmail();

            if (!$email) {
                throw new Exception(
                    "Você não tem um e-mail cadastrado no sistema. Clique <a href='" .
                        $_ENV['URL_MEULOOK'] .
                        "usuario/editar-dados'>aqui</a> para completar seu cadastro e tente novamente."
                );
            }

            $usuario = UsuarioService::buscaUsuario($this->conexao, $email);

            if (empty($usuario['token'])) {
                throw new Exception('Não foi possível enviar o e-mail. Entre em contato com o suporte.');
            }

            $link = $_ENV['URL_LOOKPAY'] . 'iToken?psw=' . $usuario['token'];
            $corpo = 'Não responder a esse e-mail. O seu link para criar a senha é: ' . $link;

            $envioDeEmail = new Email('Look Pay');
            $envioDeEmail->enviar($email, $email, 'Criar iToken Look Pay', $corpo, $corpo);

            $this->retorno['data'] = '';
            $this->retorno['message'] = 'E-mail Enviado com Sucesso!';
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
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

    public function createiToken()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'senha' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            if (mb_strlen($senha) == 6) {
                if (Pay::criaPasswordToken($this->conexao, $this->idUsuario, $senha)) {
                    $message = 'Senha Cadastrada com Sucesso.';
                }
            } else {
                throw new Exception('Senha possui menos ou mais que 6 digitos.', 1);
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

    public function buscaRecebimentoPendente()
    {
        try {
            if (Pay::existeRecebimentoPendente($this->conexao, $this->idCliente)) {
                $existe = 1;
                $prioridade = new PrioridadePagamentoService();
                $prioridade->id_colaborador = (int) $this->idCliente;
                $prioridade->situacao = 'CR';
                $lista = $prioridade->retornaTransacoes($this->conexao);
                $mount = $prioridade->buscaMontanteTransacao($this->conexao);
                $message = 'Pendencias Encontradas com Sucesso';
            } else {
                $existe = 0;
                $message = 'Nada Encontrado';
                $lista = [];
            }
            $this->respostaJson
                ->setData([
                    'status' => true,
                    'message' => $message,
                    'data' => ['existe' => $existe, 'lista' => $lista, 'mount' => $mount],
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

    public function buscaBancos($chave)
    {
        $this->conexao->beginTransaction();
        try {
            Validador::validar($chave, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            extract($chave);
            //$idZoopRemetente = Cadastro::buscaIdZoop($this->conexao, $id);
            if ($lista = Cadastro::buscaContaPrincipal_($this->conexao, $id, $this->idCliente)) {
                $message = 'Cadastro Encontrado com sucesso';
            } else {
                $message = 'Nada encontrado';
            }
            $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => $lista])->send();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function buscaDuvida()
    {
        try {
            $faq = new FAQService();
            if ($dados = $faq->buscaMobilePay($this->conexao)) {
                $message = 'Sucess';
            } else {
                $message = 'Nada Encontrado';
            }
            $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => $dados])->send();
        } catch (\Throwable $e) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function buscaDuvidas($chave)
    {
        try {
            Validador::validar($chave, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);
            extract($chave);
            $faq = new FAQService();
            if ($dados = $faq->buscaMobilePay($this->conexao, $id)) {
                $message = 'Sucess';
            } else {
                $message = 'Nada Encontrado';
            }
            $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => $dados])->send();
        } catch (\Throwable $e) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function criaDuvida()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'pergunta' => [Validador::OBRIGATORIO, Validador::SANIZAR, Validador::STRING],
                'tipo' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            $faq = new FAQService();
            $faq->pergunta = $pergunta;
            $faq->id_cliente = $this->idCliente;
            if ($faq->inserir($this->conexao)) {
                $message = 'Sucess';
            } else {
                throw new Exception('Erro ao inserir duvida');
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

    public function respondeDuvida()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'resposta' => [Validador::OBRIGATORIO],
                'id' => [Validador::OBRIGATORIO],
            ]);
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

    public function editFrequency()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'frequencia' => [Validador::OBRIGATORIO],
                'id' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            $faq = new FAQService();
            $faq->frequencia = $frequencia;
            $faq->id = (int) $id;
            if ($faq->atualizar($this->conexao)) {
                $message = 'Sucesso';
            } else {
                throw new Exception('Erro ao adicionar prioridade');
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

    /**
     * Cadastro na IUGU do Look Pay
     */
    public function cadastraContatoMobilePay()
    {
        try {
            //INICIAR TRANSACAO
            $this->conexao->beginTransaction();
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            ################################# Validando Dados ################################
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'holder' => [Validador::OBRIGATORIO, Validador::STRING],
                'account' => [Validador::OBRIGATORIO],
                'agency' => [Validador::OBRIGATORIO],
                'regime' => [Validador::OBRIGATORIO],
                'register' => [Validador::OBRIGATORIO],
                'phone' => [Validador::OBRIGATORIO],
                'type_account' => [Validador::OBRIGATORIO],
            ]);
            $type_register = 'F';
            $colaborador = new ColaboradoresRepository();
            $dadosColaborador = $colaborador->busca(['id' => 1]);
            extract($dadosJson);
            if ($regime == 2) {
                Validador::validar($dadosJson, [
                    'register' => [Validador::OBRIGATORIO, Validador::CPF],
                ]);
                $type_register = 'F';
                $dadosColaborador->setRegime(2);
            } elseif ($regime == 1) {
                $type_register = 'J';
                $dadosColaborador->setRegime(1);
            } elseif ($regime == 3) {
                throw new Exception('Não é possivel cadastrar uma conta bancária com tipo de regime Mobile(3)');
            } else {
                throw new Exception('Regime da conta bancária não identificado');
            }
            ################################################################################
            ##################################### Valida Conta Bancária ####################
            $iuguService = new IuguServiceConta();
            $tipo_conta = $type_account == 'checking' ? 'Corrente' : 'Poupança';
            $type = 0;
            if ($nome_bank == 'Caixa Econômica' || $bank == '104') {
                if ($regime == 1 && $tipo_conta == 'Corrente') {
                    $type = '003';
                } elseif ($regime == 2 && $tipo_conta == 'Corrente') {
                    $type = '001';
                } else {
                    $type = '013';
                }
            }
            $validador = IuguServiceConta::mask_validation($nome_bank ?? $bank, $agency, $account, $type);
            if (count($validador) > 0) {
                $agency = $validador['agencia'];
                $account = $validador['conta'];
            } else {
                throw new Exception('Não foi possível validar');
            }
            ###############################################################################
            $contaBancaria = new ContaBancaria(
                $holder,
                $bank ?? ZoopTokenContaBancariaService::buscaIDBanco($nome_bank, $this->conexao),
                $agency,
                $account,
                $register,
                $register,
                $type_account,
                $dadosColaborador
            );
            ################################### Verifica se já existe conta ##################
            if (
                $id_conta = ZoopTokenContaBancariaService::verificaExisteContaBancariaJaCadastrada(
                    $contaBancaria,
                    $this->conexao,
                    $register
                )
            ) {
                throw new Exception('Conta já cadastrada', 10);
            } else {
                ####################################### Cadastra IUGU ########################
                $cadastroSellerIugu = new IuguServiceConta();
                $cadastroSellerIugu->idSellerConta = 1;
                $cadastroSellerIugu->dadosColaboradores($this->conexao);
                $cadastroSellerIugu->arrayDados['cpf_cnpj'] = $register;
                $cadastroSellerIugu->arrayDados['phone'] = $phone;
                $cadastroSellerIugu->arrayDados['nome'] = $holder;
                $resposta = $cadastroSellerIugu->CriaContaIugo();
                ###############################################################################
                ####################################### Valida Conta ##########################
                if (
                    $id_conta = ZoopTokenContaBancariaService::criaContaBancariaPay(
                        $contaBancaria,
                        $resposta,
                        $this->conexao,
                        $phone
                    )
                ) {
                    $endereco = ColaboradoresRepository::enderecoParaTransferencia(
                        $this->conexao,
                        $dadosColaborador->getId()
                    );
                    $dados = $iuguService->verificacaoConta(
                        $resposta->account_id,
                        $resposta->live_api_token,
                        $type_register,
                        $holder,
                        $endereco['logradouro'],
                        $endereco['cep'],
                        $endereco['cidade'],
                        $phone,
                        $phone,
                        $nome_bank,
                        $agency,
                        $tipo_conta,
                        $account,
                        $register,
                        $register,
                        $this->conexao
                    );
                } else {
                    throw new Exception('Error para atualizar cadigo da iugu no banco de dados', 1);
                }
                ############################################################################
            }

            $this->conexao->commit();
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Cadastrado com sucesso', 'data' => ['id' => $id_conta]])
                ->send();
        } catch (\Throwable $e) {
            $this->conexao->rollBack();
            if ($e->getCode() == 10) {
                $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => ['id' => $id_conta]];
                $this->codigoRetorno = 201;
            } else {
                $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
                $this->codigoRetorno = 400;
            }
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function hasIugu()
    {
        //interno
        try {
            if (Pay::hasApiColaborador($this->conexao, $this->idCliente)) {
                if (Cadastro::buscaIdIugu($this->conexao, $this->idCliente)) {
                    if (Cadastro::buscaContaVerificada($this->conexao, $this->idCliente)) {
                        $message = 'Sincronizado com Sucesso';
                        $data['cadastro'] = '0';
                    } else {
                        $message = 'Conta Bancária não verificada';
                        $data['cadastro'] = '3';
                        $this->respostaJson
                            ->setData(['status' => false, 'message' => 'Não possui cadastro', 'data' => $data])
                            ->send();
                        die();
                    }
                } else {
                    $message = 'Existe Api Colaborador';
                    $data['cadastro'] = '1';
                    $this->respostaJson->setData(['status' => false, 'message' => $message, 'data' => $data])->send();
                    die();
                }
            } else {
                $data['cadastro'] = '2';
                $this->respostaJson
                    ->setData(['status' => false, 'message' => 'Não possui cadastro', 'data' => $data])
                    ->send();
                die();
            }
            $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => $data])->send();
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaCabecalho()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'token' => [Validador::OBRIGATORIO],
            ]);

            extract($dadosJson);
            if ($dados = Pay::cabecalhoMobile($this->conexao, $token)) {
                $message = 'Success';
            } else {
                throw new Exception('Nada encontrado', 420);
            }
            $this->respostaJson->setData(['status' => true, 'message' => $message, 'data' => $dados])->send();
        } catch (\Throwable $e) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function buscaDepositoAberto()
    {
        try {
            $retorno = TransacaoConsultasService::buscaDepositoAberto($this->conexao, $this->idCliente);
            if (count($retorno) > 0) {
                $retorno[0]['codCliente'] = @Cript::criptInt($retorno[0]['id']);
                $this->respostaJson
                    ->setData(['status' => false, 'message' => 'Você possui depositos em aberto', 'data' => $retorno])
                    ->send();
            } else {
                $this->respostaJson
                    ->setData(['status' => true, 'message' => 'Você não possui depositos em aberto', 'data' => 'Ok'])
                    ->send();
            }
        } catch (\Throwable $e) {
            $data = '';
            $this->respostaJson
                ->setData(['status' => false, 'message' => $e->getMessage(), 'data' => $data])
                ->setStatusCode(400)
                ->send();
        }
    }

    public function borrowing()
    {
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'g-recaptcha-response' => [Validador::SE(App::isProduction(), Validador::OBRIGATORIO)],
            'password' => [Validador::OBRIGATORIO],
            'valor_capital' => [Validador::OBRIGATORIO],
            'conta' => [Validador::OBRIGATORIO],
        ]);
        extract($dadosJson);

        if (App::isProduction()) {
            $resposta = Http::get('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => env('RECAPTCHA_SECRET'),
                'response' => $dadosJson['g-recaptcha-response'],
            ])->json();

            if (empty($resposta) || !$resposta['success']) {
                new BadRequestHttpException('Por favor, realize a verificação do reCAPTCHA corretamente!');
            }
        }

        $saldo_maximo = Pay::saldo_emprestimo(DB::getPdo(), Auth::user()->id_colaborador);
        if ($saldo_maximo['saldo'] < $valor_capital) {
            throw new BadRequestHttpException(
                'Valor precisa ser menor que o limite máximo disponível para antecipação'
            );
        }

        $password = base64_decode($password);

        if ($pass_confirm = Pay::buscaPassword(DB::getPdo(), Auth::id())) {
            $password = sha1($password);
            if ($pass_confirm != $password) {
                throw new Exception('Senha Inválida', 1);
            } else {
                $saldo = Pay::saldo(DB::getPdo(), Auth::user()->id_colaborador);
                extract($saldo);
                $valor_atual = $valor_capital;
                if ((float) $saldo > 0) {
                    $valor_atual = (float) ((float) $valor_capital - (float) $saldo);
                }
                $emprestimo = new EmprestimoService();
                $emprestimo->id_favorecido = Auth::user()->id_colaborador;
                $emprestimo->id_conta_bancaria_favorecida = (int) $conta;
                $emprestimo->valor_capital = (float) $valor_capital;
                $emprestimo->valor_atual = -(float) $valor_atual;
                $emprestimo->situacao = 'PE';
                $config = Pay::buscaConfiguracao(DB::getPdo());
                $emprestimo->taxa = $config['taxa_adiantamento'];
                if ($emprestimo->id = $emprestimo->criaEmprestimo(DB::getPdo())) {
                    //id_prioridade_saque
                    $lancamento = $emprestimo->buscaLancamentoAdiantamento(
                        DB::getPdo(),
                        (float) $valor_capital,
                        Auth::user()->id_colaborador
                    );
                    $emprestimo->id_lancamento = $lancamento['id'];
                    $emprestimo->atualizaAdiantamento(DB::getPdo());
                } else {
                    throw new Exception('Falha ao inserir adiantamento', 420);
                }
            }
        } else {
            throw new Exception('Senha Inválida', 1);
        }
        $data = [
            'transfer_id' => $lancamento['id_prioridade_saque'],
            'transfer' => $lancamento['id_prioridade_saque'],
            'payment' => (float) $valor_capital,
            'credit' => (float) 0,
            'to' => $conta,
            'from' => Auth::user()->id_colaborador,
        ];

        return $data;
    }

    public function saldoEmprestimo()
    {
        try {
            $this->retorno['data'] = Pay::saldo_emprestimo($this->conexao, $this->idCliente);
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function verificaSaldoAntecipacao()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'valor' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            $saldo = Pay::saldo_emprestimo($this->conexao, $this->idCliente);
            extract($saldo);
            if ((float) $saldo >= (float) $valor) {
                $this->respostaJson
                    ->setData(['status' => true, 'message' => 'Antecipassão Conclúida', 'data' => $saldo])
                    ->send();
            } else {
                throw new Exception('Valor deve ser menor ou igual ao valor máximo da Antecipação', 420);
            }
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaEmprestimos()
    {
        try {
            $lista = Pay::buscaEmprestimos($this->conexao, $this->idCliente);
            if ($lista) {
                $this->respostaJson
                    ->setData(['status' => true, 'message' => 'Adiantamentos encontrados', 'data' => $lista])
                    ->send();
            } else {
                $this->respostaJson
                    ->setData(['status' => false, 'message' => 'Nenhum adiantamento encontrado', 'data' => []])
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function fees()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'valor' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            $dados = Pay::calculaJuros($this->conexao, $valor);
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Adiantamentos encontrados', 'data' => $dados['juros']])
                ->send();
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function abstracts()
    {
        try {
            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );
            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id' => [Validador::OBRIGATORIO],
            ]);
            extract($dadosJson);
            $lista['extrato'] = Pay::buscaSaldos($this->conexao, $id, $this->idCliente);
            $dado = Pay::saldoAnterior($this->conexao, $id, $this->idCliente);
            $lista['saldo_anterior'] = $dado;
            $this->respostaJson
                ->setData(['status' => true, 'message' => 'Extrato encontrado', 'data' => $lista])
                ->send();
        } catch (\Throwable $e) {
            $this->retorno = ['status' => false, 'message' => $e->getMessage(), 'data' => []];
            $this->codigoRetorno = 400;
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function buscaSaldoGeral()
    {
        try {
            $this->retorno['data'] = Pay::BuscaSaldoGeral($this->conexao, $this->idCliente);
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

    public function buscaExtratoColaborador($dados)
    {
        try {
            Validador::validar($dados, [
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $dadosGet = $this->request->query->all();

            Validador::validar($dadosGet, [
                'de' => [Validador::NAO_NULO],
                'ate' => [Validador::NAO_NULO],
            ]);

            $saldoCliente = CreditosDebitosService::saldoCliente(
                $this->conexao,
                $dados['id_colaborador'],
                $dadosGet['de'],
                $dadosGet['ate']
            );
            $resultado['periodo'] = $saldoCliente[0]['saldo'];
            $resultado['anterior'] = $saldoCliente[1]['saldo'];
            $resultado['posterior'] = $saldoCliente[2]['saldo'];
            $lista = CreditosDebitosService::buscaExtratoClienteDetalhes(
                $this->conexao,
                $dados['id_colaborador'],
                $dadosGet['de'],
                $dadosGet['ate']
            );
            if (!$lista) {
                throw new Exception('Colaborador não encontrado');
            }

            $resultado['total'] = LancamentoConsultas::consultaCreditoCliente($this->conexao, $dados['id_colaborador']);
            $resultado['dados'] = $lista;
            $resultado['adiantamento_bloqueado'] = ColaboradoresService::adiantamentoEstaBloqueado(
                $this->conexao,
                $dados['id_colaborador']
            );

            $itemCima = null;

            $resultado['dados'] = array_map(
                function (array $item, int $key) use ($resultado, &$itemCima): array {
                    $item['origem'] = Lancamento::buscaTextoPelaOrigem($item['origem'], true);
                    if ($key === 0) {
                        $item['saldo_atual'] = $resultado['total'];
                        $itemCima = $item;
                        return $item;
                    }

                    $valorDoLancamento = $itemCima['tipo'] === 'R' ? $itemCima['valor'] : $itemCima['valor'] * -1;
                    $item['saldo_atual'] = round(
                        $itemCima['saldo_atual'] +
                            ($itemCima['faturamento_criado_pago'] === 'T' ? 0 : $valorDoLancamento),
                        2
                    );

                    $itemCima = $item;
                    return $item;
                },
                $resultado['dados'],
                array_keys($resultado['dados'])
            );

            $this->status = 200;
            $this->retorno['data'] = $resultado;
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

    public function buscaLancamentosFuturos(Origem $origem)
    {
        $geral = Request::boolean('geral');
        $idColaborador = Request::input('id_colaborador');

        if (!$origem->ehAdm()) {
            $idColaborador = Auth::user()->id_colaborador;
            $geral = false;
        }

        Validador::validar(
            [
                'geral' => $geral,
                'id_colaborador' => $idColaborador,
            ],
            [
                'geral' => [Validador::BOOLEANO],
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]
        );

        $lancamentosFuturos = LancamentoConsultas::buscaLancamentosFuturos($idColaborador, $geral);
        $creditoVendasNaoEntregues = LancamentoConsultas::buscaValorTotalLancamentosPendentes($idColaborador);
        return [
            'lancamentos' => $lancamentosFuturos,
            'credito_vendas_nao_entregues' => $creditoVendasNaoEntregues,
        ];
    }

    public function buscaValorTotalLancamentosPendentes()
    {
        $data = LancamentoConsultas::buscaValorTotalLancamentosPendentes(Auth::user()->id_colaborador);
        return $data;
    }

    public function geraLancamento($dados)
    {
        try {
            $this->conexao->beginTransaction();

            if ($_ENV['AMBIENTE'] === 'producao' && !in_array($this->idUsuario, [356, 526])) {
                throw new Exception('Este usuário não tem permissão para esse tipo de ação');
            }

            Validador::validar($dados, [
                'id_cliente' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::OBRIGATORIO, Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);
            if ($dadosJson['valor'] <= 0) {
                throw new Exception('O valor não pode ser negativo');
            }

            Validador::validar($dadosJson, [
                'debito_ou_credito' => [Validador::ENUM('P', 'R')],
                'valor' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $lancamento = new Lancamento(
                $dadosJson['debito_ou_credito'],
                1,
                'MA',
                $dados['id_cliente'],
                (new \DateTime())->format('Y-m-d H:i:s'),
                $dadosJson['valor'],
                $this->idCliente,
                1
            );
            $lancamento->documento = 7;
            $lancamento->documento_pagamento = 7;
            $lancamento->observacao = $dadosJson['motivo'];
            LancamentoCrud::salva($this->conexao, $lancamento);

            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
            $this->conexao->commit();
        } catch (Exception $e) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = $e->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaListaJuros()
    {
        try {
            $this->retorno['data'] = Taxas::buscaPorcentagemJuros($this->conexao);
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function buscaTaxaAdiantamento()
    {
        try {
            $this->retorno['data'] = ConfiguracaoService::buscaTaxaAdiantamento($this->conexao);
            $this->retorno['status'] = true;
            $this->codigoRetorno = 200;
        } catch (\Throwable $e) {
            $this->retorno['message'] = $e->getMessage();
            $this->retorno['status'] = false;
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function abatesLancamento(int $idLancamento, LancamentoConsultas $lancamentoConsultas)
    {
        $credito = $lancamentoConsultas->consultaCreditoNormalOuPendente($idLancamento);

        if (empty($credito)) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $lancamentos = $lancamentoConsultas->consultaAbatesCredito($credito);

        array_unshift($lancamentos, [
            'data_emissao' => $credito['data_emissao'],
            'valor_pago' => $credito['valor'],
            'id' => $credito['id'],
            'transacao_origem' => $credito['transacao_origem'],
        ]);

        return $lancamentos;
    }
}
