<?php

namespace MobileStock\service\IuguService;

use Exception;
use InvalidArgumentException;
use MobileStock\database\Conexao;
use MobileStock\helper\DB;
use MobileStock\model\PagamentoIugu\PagamentosIugu;

class IuguServiceConta extends PagamentosIugu
{
    public $arrayDados;

    /*protected $account_id;
    protected $name;
    protected $test_api_token;
    protected $user_token;*/
    protected $arrayConta;
    public function __construct()
    {
        parent::__construct();
        $arrayDados = [];
    }

    /**
     * @see https://github.com/mobilestock/web/issues/3058
     */
    public function dadosColaboradores(\PDO $conexao = null)
    {
        $conexao = $conexao ?? Conexao::criarConexao();

        $this->arrayDados = $conexao
            ->query(
                "SELECT
                                                colaboradores.email,
                                                colaboradores.razao_social nome,
                                                COALESCE(SUBSTRING(REGEXP_REPLACE(REPLACE(colaboradores.telefone,'+55',''),'[()+-]',''),3),'') phone,
                                                COALESCE(CONCAT('0',SUBSTRING(REGEXP_REPLACE(REPLACE(colaboradores.telefone,'+55',''),'[()+-\]',''),1,2)),'') phone_prefix,
                                                IF(colaboradores.regime = 1, colaboradores.cnpj, colaboradores.cpf) cpf_cnpj,
                                                COALESCE(colaboradores_enderecos.cep,'') zip_code,
                                                COALESCE(colaboradores_enderecos.numero,'') number,
                                                COALESCE(colaboradores_enderecos.logradouro,'') street,
                                                COALESCE(colaboradores_enderecos.cidade,'') city,
                                                COALESCE(colaboradores_enderecos.uf,'') state,
                                                COALESCE(colaboradores_enderecos.bairro,'') district
                                                FROM colaboradores
                                                LEFT JOIN colaboradores_enderecos ON
                                                    colaboradores_enderecos.id_colaborador = colaboradores.id
                                                    AND colaboradores_enderecos.eh_endereco_padrao = 1
                                                WHERE colaboradores.id = " .
                    $this->idSellerConta .
                    "
                                                    AND colaboradores.razao_social IS NOT NULL
                                                    AND colaboradores.email IS NOT NULL"
            )
            ->fetch(\PDO::FETCH_ASSOC);
    }

    public function criaContaIugo()
    {
        $this->jsonEnvio = '{"name":"' . $this->arrayDados['nome'] . '"}';
        $this->method = 'POST';
        $this->url = 'https://api.iugu.com/v1/marketplace/create_account';
        $resposta = $this->requestIugu();
        if ($resposta['codigo'] !== 200) {
            $this->retornoErro();
        }
        $this->arrayConta = $resposta['resposta'];
        $this->configuraConta();
        return $this->arrayConta;
    }
    public function configuraConta()
    {
        $this->apiToken = $this->arrayConta->user_token;
        $this->jsonEnvio =
            '{"commission_percent":"0","auto_withdraw":false,"fines":false,"per_day_interest":false,"auto_advance":false}';
        $this->method = 'POST';
        $this->url = 'https://api.iugu.com/v1/accounts/configuration';
        $resposta = $this->requestIugu();
        if ($resposta['codigo'] !== 200) {
            $this->retornoErro();
        }
    }

    public function verificacaoConta(
        string $idIugu,
        string $iuguTokenLive,
        string $regime,
        string $razaoSocial,
        string $endereco,
        string $cep,
        string $cidade,
        string $estado,
        string $telefone,
        string $nomeBanco,
        string $agencia,
        string $tipoConta,
        string $numeroConta,
        string $cpf = '',
        string $cnpj = '',
        ?\PDO $conexao = null
    ): array {
        $personType = $regime === 'F' ? 'Pessoa Física' : 'Pessoa Jurídica';

        if ($regime === 'F' && !$cpf) {
            throw new InvalidArgumentException('Cpf obrigatório');
        }

        if ($regime === 'J' && !$cnpj) {
            throw new InvalidArgumentException('Cnpj obrigatório');
        }

        if ($regime === 'M') {
            throw new Exception('Não foi possível identificar regime Mobile(3)');
        }

        $this->apiToken = $iuguTokenLive;

        $this->jsonEnvio = json_encode([
            'data' => [
                'price_range' => 'Mais que R$ 500,00',
                'physical_products' => true,
                'business_type' => 'Venda',
                'person_type' => $personType,
                'automatic_transfer' => false,
                'cpf' => $cpf,
                'cnpj' => $cnpj,
                'company_name' => $razaoSocial,
                'name' => $razaoSocial,
                'address' => $endereco,
                'cep' => $cep,
                'city' => $cidade,
                'state' => $estado,
                'telephone' => $telefone,
                'resp_name' => $razaoSocial,
                'resp_cpf' => $cpf,
                'bank' => $nomeBanco,
                'bank_ag' => $agencia,
                'account_type' => $tipoConta,
                'bank_cc' => $numeroConta,
            ],
        ]);
        $this->method = 'POST';
        $this->url = "https://api.iugu.com/v1/accounts/$idIugu/request_verification";
        $resposta = $this->requestIugu();
        if ($resposta['codigo'] !== 200) {
            $this->retornoErro();
        }

        DB::exec(
            "UPDATE conta_bancaria_colaboradores SET conta_bancaria_colaboradores.conta_iugu_verificada = 'T' WHERE conta_bancaria_colaboradores.id_iugu = ?",
            [$idIugu],
            $conexao
        );
        return $resposta;
    }

    // public function cadastraContaBancaria(array $contaBancaria, string $idIugu){
    //     $this->jsonEnvio = json_encode($contaBancaria);
    //     $this->apiToken = $idIugu;
    //     $this->ApiToken = $idIugu;
    //     $this->method = 'POST';
    //     $this->url = 'https://api.iugu.com/v1/bank_verification';
    //     $resposta = $this->requestIugu();
    //     if ($resposta['codigo'] !== 200) {
    //         $this->retornoErro();
    //     }
    //     $this->arrayConta = $resposta['resposta'];
    //     $this->configuraConta();
    //     return  $this->arrayConta;
    // }

    public static function mask_validation($bank_code, $agency, $count, $type = 0)
    {
        $agency = str_replace('-', '', $agency);
        //$agency = intVal($agency);
        $count = str_replace('-', '', $count);
        //$count = intVal($count);
        switch ($bank_code) {
            case 'Banco do Brasil': //BB
            case '001': //BB
                if (mb_strlen($agency) < 5) {
                    $agency = str_pad($agency, 5, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 6) {
                    $agency = mb_substr($agency, 0, -1);
                }
                if (mb_strlen($agency) == 5) {
                    $agencia = mb_substr($agency, 0, -1);
                    $agencia .= '-';
                    $agencia .= mb_substr($agency, -1);
                } else {
                    return [];
                }
                if (mb_strlen($count) < 8) {
                    $count = str_pad($count, 8, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($count) == 8) {
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;
            case 'Santander': //Santander
            case '033': //Santander
            case 'PagSeguro': //PagSeguro
            case '290': //PagSeguro
            case 'Safra': //Safra
            case '422': //Safra
            case 'Banestes': //Banestes
            case '021': //Banestes
            case 'Unicred': //Unicred
            case '136': //Unicred
            case 'Mercantil do Brasil': //Mercantil do Brasil
            case '389': //Mercantil do Brasil
            case 'Gerencianet Pagamentos do Brasil': //Gerencianet Pagamentos do Brasil
            case '364': //Gerencianet Pagamentos do Brasil
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }
                if (mb_strlen($count) < 9) {
                    $count = str_pad($count, 9, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) == 4 && mb_strlen($count) == 9) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;
            case 'Caixa Econômica': //Caixa
            case '104': //Caixa
                if (mb_strlen($count) < 9) {
                    $count = str_pad($count, 9, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }
                if (mb_strlen($agency) == 4 && mb_strlen($count) == 10) {
                    $agencia = $agency;
                    $conta = $type . mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } elseif (mb_strlen($agency) == 4 && mb_strlen($count) == 9) {
                    $agencia = $agency;
                    $conta = $type . mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;
            case 'Bradesco': //Bradesco
            case '237': //Bradesco
                if (mb_strlen($count) < 8) {
                    $count = str_pad($count, 8, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 5) {
                    throw new Exception('Digito da agência é obrigatório em contas bancárias Bradesco: 9999-D ');
                } elseif (mb_strlen($agency) == 6) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 5) {
                    $agencia = mb_substr($agency, 0, -1);
                    $agencia .= '-';
                    $agencia .= mb_substr($agency, -1);
                } else {
                    return [];
                }

                if (mb_strlen($count) == 8) {
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-' . mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'Itaú': //Itau
            case '341': //Itau
            case 'Banco Topazio': //Banco Topazio
            case '082': //Banco Topazio
            case 'Uniprime': //Uniprime
            case '099': //Uniprime
                if (mb_strlen($count) < 6) {
                    $count = str_pad($count, 6, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }
                if (mb_strlen($agency) == 4 && mb_strlen($count) == 6) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'Agibank': //Agibank
                if (mb_strlen($agency) == 4 && mb_strlen($count) == 10) {
                    $agencia = $agency;
                    $conta = $count;
                } else {
                    return [];
                }
                break;

            case 'Banpará': //Banpará
            case 'Banrisul': //Banrisul
            case 'Sicoob': //Sicoob
            case '756': //Sicoob
            case 'Inter': //Inter
            case '077': //Inter
            case 'BRB': //BRB
            case '070': //BRB
            case 'Neon/Votorantim': //Neon/Votorantim
            case '655': //Neon/Votorantim
            case 'Votorantim': //Neon/Votorantim
            case 'Neon': //Neon/Votorantim
            case 'Modal': //Modal
            case '746': //Modal
                if (mb_strlen($count) < 10) {
                    $count = str_pad($count, 10, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }
                if (mb_strlen($agency) == 4 && mb_strlen($count) == 10) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'Sicredi': //Sicredi
            case '748': //Sicredi
                if (mb_strlen($count) < 7) {
                    $count = str_pad($count, 7, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }
                if (mb_strlen($agency) == 4 && mb_strlen($count) == 7) {
                    $agencia = $agency;
                    $conta = $count;
                } else {
                    return [];
                }
                break;

            case 'Via Credi': //Via Credi
            case '085': //Via Credi
            case 'JP Morgan': //JP Morgan
            case '376': //JP Morgan
                if (mb_strlen($count) < 12) {
                    $count = str_pad($count, 12, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }
                if (mb_strlen($agency) == 4 && mb_strlen($count) == 12) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'Nubank': //Nubank
            case '260': //Nubank
            case 'PJBank': //PJBank
            case '301': //PJBank
            case 'Juno': //Juno
            case '383': //Juno
                if (mb_strlen($count) < 11) {
                    $count = str_pad($count, 11, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }
                if (mb_strlen($agency) == 4 && mb_strlen($count) == 11) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'Banco Original': //Banco Original
            case '212': //Banco Original
            case 'Banco C6': //Banco C6
            case '336': //Banco C6
            case 'Stone': //Stone
            case '197': //Stone
            case 'Cooperativa Central de Credito Noroeste Brasileiro': //Cooperativa Central de Credito Noroeste Brasileiro
            case '97': //Cooperativa Central de Credito Noroeste Brasileiro
            case 'Cora': //Cora
            case '403': //Cora
                if (mb_strlen($count) < 8) {
                    $count = str_pad($count, 8, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 4 && mb_strlen($count) == 8) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'Money Plus': //Money Plus
            case '274': //Money Plus
                if (mb_strlen($count) < 9) {
                    $count = str_pad($count, 9, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) > 1) {
                    $agency = mb_substr($agency, -1);
                }

                if (mb_strlen($agency) == 1 && mb_strlen($count) == 9) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'BS2': //BS2
            case '218': //BS2
            case 'Banco Daycoval': //Banco Daycoval
            case '707': //Banco Daycoval
            case 'Uniprime Norte do Paraná': //Uniprime Norte do Paraná
            case '084': //Uniprime Norte do Paraná
            case 'Banco da Amazonia': //Banco da Amazonia
            case '003': //Banco da Amazonia
                if (mb_strlen($count) < 7) {
                    $count = str_pad($count, 7, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 4 && mb_strlen($count) == 7) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'Rendimento': //Rendimento
            case '633': //Rendimento
                if (mb_strlen($count) < 10) {
                    $count = str_pad($count, 10, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 5) {
                    $agency = str_pad($agency, 5, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 6) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 5) {
                    $agencia = mb_substr($agency, 0, -1);
                    $agencia .= '-';
                    $agencia .= mb_substr($agency, -1);
                } else {
                    return [];
                }
                if (mb_strlen($count) == 10) {
                    $conta = $count;
                } else {
                    return [];
                }
                break;

            case 'Banco do Nordeste': //Banco do Nordeste
            case '004': //Banco do Nordeste
            case 'BRL Trust DTVM': //BRL Trust DTVM
            case '173': //BRL Trust DTVM
                if (mb_strlen($count) < 7) {
                    $count = str_pad($count, 7, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 3) {
                    $agency = str_pad($agency, 3, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 4) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 3 && mb_strlen($count) == 7) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'Citibank': // Citibank
            case '745': // Citibank
                if (mb_strlen($count) < 8) {
                    $count = str_pad($count, 8, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 4 && mb_strlen($count) == 8) {
                    $agencia = $agency;
                    $conta = $count;
                } else {
                    return [];
                }
                break;

            case 'Global SCM': //Global SCM
            case '384': //Global SCM
                if (mb_strlen($count) < 11) {
                    $count = str_pad($count, 11, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }
                if (mb_strlen($agency) == 4 && mb_strlen($count) == 11) {
                    $agencia = $agency;
                    $conta = $count;
                } else {
                    return [];
                }
                break;

            case 'Mercado Pago': //Mercado Pago
            case '323': //Mercado Pago
                if (mb_strlen($count) < 14) {
                    $count = str_pad($count, 14, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 4) {
                    $agency = str_pad($agency, 4, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 5) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 4 && mb_strlen($count) == 14) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'BNP Paribas Brasil': //BNP Paribas Brasil
            case '752': //BNP Paribas Brasil
                if (mb_strlen($count) < 9) {
                    $count = str_pad($count, 9, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 3) {
                    $agency = str_pad($agency, 3, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 4) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 3 && mb_strlen($count) == 9) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -3);
                    $conta .= '-';
                    $conta .= mb_substr($count, -3, 0);
                } else {
                    return [];
                }
                break;

            case 'Cresol': //Cresol
            case '133': //Cresol
                if (mb_strlen($count) < 6) {
                    $count = str_pad($count, 6, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 5) {
                    $agency = str_pad($agency, 5, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 6) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 5) {
                    $agencia = mb_substr($agency, 0, -1);
                    $agencia .= '-';
                    $agencia .= mb_substr($agency, -1);
                } else {
                    return [];
                }
                if (mb_strlen($count) == 6) {
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;

            case 'Banco Banese': //Banco Banese
            case '047': //Banco Banese
                if (mb_strlen($count) < 9) {
                    $count = str_pad($count, 9, '0', STR_PAD_LEFT);
                }
                if (mb_strlen($agency) < 3) {
                    $agency = str_pad($agency, 3, '0', STR_PAD_LEFT);
                } elseif (mb_strlen($agency) == 4) {
                    $agency = mb_substr($agency, 0, -1);
                }

                if (mb_strlen($agency) == 3 && mb_strlen($count) == 9) {
                    $agencia = $agency;
                    $conta = mb_substr($count, 0, -1);
                    $conta .= '-';
                    $conta .= mb_substr($count, -1);
                } else {
                    return [];
                }
                break;
        }
        return ['agencia' => $agencia, 'conta' => $conta];
    }
    // public static function buscaToken(\PDO $conexao = null,array $dados){
    //     $conexao = $conexao ?? Conexao::criarConexao();
    //     $sql = 'SELECT iugu_token_live FROM api_colaboradores WHERE 1=1 ';
    //     foreach($dados as $collumn => $value):
    //         if(is_string($value)){
    //             $sql.="AND $collumn LIKE '{$value}'";
    //         }else{
    //             $sql .= "AND $collumn = {$value}";
    //         }
    //     endforeach;
    //     $stmt = $conexao->prepare($sql);
    //     $stmt->execute();
    //     $lista = $stmt->fetch(\PDO::FETCH_ASSOC);
    //     return $lista['iugu_token_live'];

    // }

    //Próximo programador que passar por aqui deve mudar tanto os metadados antigos quantos os novos na IUGU
    public function transfereDinheiroMobile(
        int $valor,
        $variaveisCustomizadas = [['name' => 'tipo', 'value' => 'Transferencia manual mobile pay']]
    ) {
        if ($_ENV['AMBIENTE'] !== 'producao') {
            return;
        }

        $this->jsonEnvio =
            '{"receiver_id":"' .
            $_ENV['DADOS_PAGAMENTO_IUGUCONTAMOBILE'] .
            '","amount_cents": ' .
            $valor .
            ', "custom_variables":' .
            json_encode($variaveisCustomizadas) .
            '}';
        $this->method = 'POST';
        $this->url = 'https://api.iugu.com/v1/transfers';
        $resposta = $this->requestIugu();
        if ($resposta['codigo'] !== 200) {
            $this->retornoErro();
        }
    }

    public function existeTransferenciaSplit(int $idSplit): bool
    {
        $this->url = 'https://api.iugu.com/v1/transfers';
        $this->method = 'GET';
        $this->complementoUrl =
            '&' .
            http_build_query([
                'custom_variables_name' => 'id_split',
                'custom_variables_value' => $idSplit,
                'limit' => 1500,
            ]);

        $resposta = $this->requestIugu();
        if ($resposta['codigo'] !== 200) {
            $this->retornoErro();
        }

        return count($resposta['resposta']->sent) > 0;
    }
}
