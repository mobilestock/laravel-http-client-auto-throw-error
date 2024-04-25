<?php
namespace MobileStock\repository;

use Exception;
use Illuminate\Support\Facades\DB;
use MobileStock\model\PacReverso;
use PDO;

// use PhpSigep\Model\Remetente;

/**
 * classe de gerenciamento de defeitos de produtos
 *
 * @author gustavo
 */
class DefeitosRepository
{
    // public function listar(array $params): array
    // {
    //     return $params;
    // }
    // public function criar(array $objetos): array
    // {
    //     return [];
    // }
    public function gerenciarPac(int $idCliente, int $idTransacao): string
    {
        $pacReverso = $this->buscaPacReverso($idCliente);
        if (empty($pacReverso)) {
            $resultado = $this->criarPac($idCliente, $idTransacao);
            if (!$resultado['success']) {
                throw new Exception('Erro ao criar PAC reverso');
            }

            $pacReverso = $resultado['success']['numeroColeta'];
            self::inserePacReverso($idCliente, 0, $pacReverso, $resultado['success']['prazo'], 0, '0');
        }

        return $pacReverso;
    }
    public function criarPac(int $idCliente, int $idTransacao): array
    {
        $pac = app(PacReverso::class, [
            'id_cliente' => $idCliente,
            'id_transacao' => $idTransacao,
            'valor_devolucao' => 30,
        ]);
        $result = $pac->solicitarPac();
        $numeroColetaSplit = preg_split('<numero_coleta>', $result, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $numeroColetaArray = $numeroColetaSplit[1][0] ?? '';
        $numeroColeta = trim($numeroColetaArray, '< / >');

        $prazoSplit = preg_split('<prazo>', $result, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $prazoArray = $prazoSplit[1][0] ?? '';
        $prazo = trim($prazoArray, '< / >');
        $prazo = date('Y-m-d');
        $prazo = date('Y-m-d', strtotime($prazo . ' + 10 days'));

        $erroSplit = preg_split('<msg_erro>', $result, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $erroArray = $erroSplit[1][0];
        $erro = trim($erroArray, '< / >');
        //<descricao_erro></descricao_erro>
        $descricaoErroSplit = preg_split('<descricao_erro>', $result, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $descricaoErroArray = $descricaoErroSplit[1][0];
        $descricaoErro = trim($descricaoErroArray, '< / >');

        if ($erro != '') {
            throw new Exception($erro);
        } elseif ($numeroColeta != '') {
            return ['success' => ['numeroColeta' => $numeroColeta, 'prazo' => $prazo]];
        } else {
            $erro = $descricaoErro;
            throw new Exception($erro . ' - ' . $result);
        }
    }

    // public function atualizar(): bool
    // {
    //     return true;
    // }

    // public function apagar(): bool
    // {
    //     return true;
    // }

    // private function buscaRemetente(int $id): array
    // {
    //     $conexao = Conexao::criarConexao();
    //     $query = $conexao->prepare('SELECT * FROM colaboradores WHERE id = :id');
    //     $query->bindParam(':id', $id, PDO::PARAM_INT);
    //     return $query->execute()
    //         ? $query->fetch(PDO::FETCH_ASSOC)
    //         : $query->errorCode();
    // }

    public function verificaSeNumeroEstaVencido(PDO $conexao, int $idCliente): bool
    {
        $sql = $conexao->prepare(
            "SELECT 1
            FROM correios_atendimento
            WHERE correios_atendimento.id_cliente = :id_cliente
                AND correios_atendimento.status='A'
                AND correios_atendimento.prazo > NOW() LIMIT 1"
        );
        $sql->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $sql->execute();
        $response = !!$sql->fetch(PDO::FETCH_ASSOC);
        return $response;
    }

    public function buscaPacReverso(int $idCliente): ?string
    {
        $stmt = "SELECT correios_atendimento.numeroColeta
            FROM correios_atendimento
            WHERE correios_atendimento.id_cliente = :id_cliente
                AND correios_atendimento.status = 'A'
                AND correios_atendimento.prazo > NOW()
            ORDER BY correios_atendimento.id DESC
            LIMIT 1;";

        $numeroColeta = DB::selectOneColumn($stmt, ['id_cliente' => $idCliente]);

        return $numeroColeta;
    }

    public static function inserePacReverso(
        int $idCliente,
        int $idAtendimento,
        int $numeroColeta,
        string $dataObj,
        int $idObj,
        string $statusObj
    ): void {
        $query = "INSERT INTO correios_atendimento(
                    correios_atendimento.id_cliente,
                    correios_atendimento.id_atendimento,
                    correios_atendimento.numeroColeta,
                    correios_atendimento.prazo,
                    correios_atendimento.idObjeto,
                    correios_atendimento.statusObjeto
                )
                VALUES(
                    :idCliente,
                    :idAtendimento,
                    :numeroColeta,
                    :prazo,
                    :idObj,
                    :statusObj);";

        DB::insert($query, [
            'idCliente' => $idCliente,
            'idAtendimento' => $idAtendimento,
            'numeroColeta' => $numeroColeta,
            'prazo' => $dataObj,
            'idObj' => $idObj,
            'statusObj' => $statusObj,
        ]);
    }
}
