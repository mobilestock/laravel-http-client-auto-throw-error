<?php /*

namespace MobileStock\model;

use Aws\S3\S3Client;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\helper\Globals;
use PDO;
use PDOException;

class Fiscal
{

    public function geraOrdemEmissaoNotaFiscal(PDO $conexao, int $tipo_frete, int $id_cliente, int $id_faturamento, array $produtos): bool
    {
        $existeOrdemGerada = $this->existeOrdemGeradaFiscal($conexao, $id_faturamento);

        if (!$existeOrdemGerada) {

            $f = $this->buscaDadosFaturamento($conexao, $id_faturamento);

            $chave = 0;
            $valor = 0;
            foreach ($produtos as $key => $p) {
                if ($valor < $p['valor_total']) {
                    $valor = $p['valor_total'];
                    $chave = $key;
                }
            }

            if ($tipo_frete == 2) {
                $produtos[$chave]['emissao_nota'] = 2;
            }

            foreach ($produtos as $key => $p) {
                $emissaoNota = $p['emissao_nota'] == 2 ? 2 : 1;
                $this->insereOrdemDeEmissaoNotaFiscal(
                    $conexao,
                    $id_faturamento,
                    intval($p['id_fornecedor']),
                    intVal($id_cliente),
                    floatVal($p['valor_total']),
                    intVal($p['pares']),
                    $emissaoNota
                );
            }
            return true;
        }
        return false;
    }

    public function buscaDadosFaturamento(PDO $conexao, int $id_faturamento)
    {
        $query = "SELECT f.id_cliente, f.transportadora, f.tipo_frete, f.tabela_preco, f.valor_frete,
        (SELECT c.regime FROM colaboradores c WHERE c.id=f.id_cliente) regime
        FROM faturamento f WHERE f.id={$id_faturamento}";
        $resultado = $conexao->query($query);
        return $resultado->fetch(PDO::FETCH_ASSOC);
    }

    public function insereOrdemDeEmissaoNotaFiscal(
        PDO $conexao,
        int $id_faturamento,
        int $idFornecedor,
        int $idCliente,
        float $valor,
        int $pares,
        int $emissaoNota
    ) {
        date_default_timezone_set('America/Sao_Paulo');
        $dataCadastro = date('Y-m-d H:i:s');
        $sth = $conexao->prepare(
            "INSERT INTO nota_fiscal_saida
            (data_cadastro,
            id_fornecedor,
            id_cliente,
            pares,
            valor,
            status_fiscal,
            id_faturamento) VALUES
            (:dataCadastro,
            :idFornecedor,
            :idCliente,
            :pares,
            :valor,
            :emissaoNota,
            :id_faturamento);"
        );
        $sth->bindValue("dataCadastro", $dataCadastro, PDO::PARAM_STR);
        $sth->bindValue("idFornecedor", $idFornecedor, PDO::PARAM_INT);
        $sth->bindValue("idCliente", $idCliente, PDO::PARAM_INT);
        $sth->bindValue("pares", $pares, PDO::PARAM_INT);
        $sth->bindValue("valor", $valor, PDO::PARAM_STR);
        $sth->bindValue("emissaoNota", $emissaoNota, PDO::PARAM_INT);
        $sth->bindValue("id_faturamento", $id_faturamento, PDO::PARAM_INT);
        return $sth->execute();
    }

    public function existeOrdemGeradaFiscal(PDO $conexao, int $id_faturamento)
    {
        $query = "SELECT * FROM nota_fiscal_saida WHERE id_faturamento={$id_faturamento};";
        $resultado = $conexao->query($query);
        return $resultado->fetch();
    }

    // public function buscaProdutosFaturamentoPorConsignacao(int $id_faturamento)
    // {
    //     $query = "SELECT fi.preco, p.consignado, p.id_fornecedor, fi.id_cliente
    //     (SELECT af.id_zoop FROM api_fornecedores af WHERE af.fk_fornecedor = p.id_fornecedor) id_mobile_pay
    //     FROM faturamento_item fi
    //     INNER JOIN produtos p ON (p.id=fi.id_produto)
    //     WHERE fi.id_faturamento = {$id_faturamento};";
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
    //     return $lista;
    // }

    public function recuperaInformacaoFiscal(int $id_faturamento)
    {
        $query = "SELECT * FROM nota_fiscal_saida WHERE id_faturamento={$id_faturamento};";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $lista = $resultado->fetchAll(PDO::FETCH_ASSOC);
        return $lista;
    }

    public function listaNotasFiscaisGeradas(array $post)
    {
        date_default_timezone_set('America/Sao_Paulo');
        extract($post);

        $pesquisa = explode('-', $filtro);

        $offset = $pagina ? $pagina * 10 - 10 : 0;

        switch ($pesquisa[2]) {

            case 'pedido':

                $campos .= " AND nfs.id_faturamento LIKE '%$busca%'";
                break;
            case 'fornecedor':

                $campos .= " AND LOWER(cf.razao_social) LIKE LOWER('%{$busca}%')";
                break;
            case 'status':

                $campos .= ' AND nfs.status_fiscal=' . $busca;
                break;
            case 'data':

                $campos .= " AND nfs.data_cadastro LIKE '%{$busca}%'";
                break;

            default:
                if ($busca) {
                    $campos .= " AND LOWER(cf.razao_social) LIKE LOWER('%{$busca}%') OR nfs.id_faturamento LIKE '%$busca%'";
                }
                break;
        }

        $query = "SELECT nfs.id_faturamento pedido, 
        DATE_FORMAT(nfs.data_cadastro, '%d/%m/%Y') AS data_cadastro,
        cf.razao_social fornecedor,
        cf.telefone,
        nfs.id_fornecedor id_fornecedor,
        cc.razao_social cliente,
        ct.razao_social transportadora,
        nfs.valor,
        nfs.frete,
        nfs.status_fiscal,
        nfs.anexo_pdf,
        nfs.nota_fiscal,
         (SELECT faturamento.tipo_frete FROM faturamento WHERE faturamento.id = nfs.id_faturamento)as tipo_frete,
        nfs.volumes
        from nota_fiscal_saida nfs
        INNER JOIN colaboradores cf ON (cf.id=nfs.id_fornecedor)
        INNER JOIN colaboradores cc ON (cc.id=nfs.id_cliente)
        INNER JOIN colaboradores ct ON (ct.id=nfs.id_transportadora)
        WHERE nfs.bloqueado=0
        {$campos}
        ORDER BY status_fiscal DESC
         LIMIT 10 OFFSET $offset;";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

    public function totalNotasFiscaisStatus()
    {
        $resultado = [];
        $query = "SELECT COUNT(*)opcional FROM nota_fiscal_saida WHERE nota_fiscal_saida.status_fiscal = 1 AND nota_fiscal_saida.bloqueado=0";
        $conexao = Conexao::criarConexao();
        $total = $conexao->query($query);
        array_push($resultado, $total->fetch(PDO::FETCH_ASSOC));
        $query = "SELECT COUNT(*)transportadora FROM nota_fiscal_saida WHERE nota_fiscal_saida.status_fiscal = 2 AND nota_fiscal_saida.bloqueado=0";
        $conexao = Conexao::criarConexao();
        $total = $conexao->query($query);
        array_push($resultado, $total->fetch(PDO::FETCH_ASSOC));
        $query = "SELECT COUNT(*)seller FROM nota_fiscal_saida WHERE nota_fiscal_saida.status_fiscal = 3 AND nota_fiscal_saida.bloqueado=0";
        $conexao = Conexao::criarConexao();
        $total = $conexao->query($query);
        array_push($resultado, $total->fetch(PDO::FETCH_ASSOC));
        $query = "SELECT COUNT(*)emitida FROM nota_fiscal_saida WHERE nota_fiscal_saida.status_fiscal = 4 AND nota_fiscal_saida.bloqueado=0";
        $conexao = Conexao::criarConexao();
        $total = $conexao->query($query);
        array_push($resultado, $total->fetch(PDO::FETCH_ASSOC));
        $query = "SELECT COUNT(*)entregue FROM nota_fiscal_saida WHERE nota_fiscal_saida.status_fiscal = 5 AND nota_fiscal_saida.bloqueado=0";
        $conexao = Conexao::criarConexao();
        $total = $conexao->query($query);
        array_push($resultado, $total->fetch(PDO::FETCH_ASSOC));
        return $resultado;
    }

    public function listaNotasFiscaisGeradasStatus(array $post)
    {
        date_default_timezone_set('America/Sao_Paulo');
        extract($post);
        $name = explode('-', $filtro);
        if ($post['filtro'] === 'undefined') {
            $name[2] = "razao_social";
        }
        $campos = ' nfs.status_fiscal=' . $status;
        $offset = $pagina ? $pagina * 10 - 10 : 0;
        switch ($name[2]) {

            case 'pedido':

                $campos .= " AND nfs.id_faturamento LIKE '%$valor%'";
                break;
            case 'fornecedor':

                $campos .= " AND LOWER(cf.razao_social) LIKE LOWER('%{$valor}%')";
                break;
            case 'transportadoras':

                $campos .= " AND ct.id = $valor";
                break;
            case 'data':

                $campos .= " AND nfs.data_cadastro LIKE '%{$valor}%'";
                break;

            default:
                if ($valor) {
                    $campos .= " AND LOWER(cc.razao_social) LIKE LOWER('%{$valor}%')";
                }
                break;
        }

        $ordena = ' ORDER BY bloqueado ASC, id_faturamento ASC';

        if ($status == 4) {
            $campos .= " AND id_transportadora > 0 ";
        }

        if ($status == 5) {
            $ordena = ' ORDER BY nfs.data_emissao DESC';
            $limite = " LIMIT 10 OFFSET {$offset}";
        } else {
            $limite = '';
        }
        $query = "SELECT nfs.id_faturamento pedido, DATE_FORMAT(nfs.data_cadastro, '%d/%m/%Y %H:%i:%s') AS data_cadastro,
        cf.razao_social fornecedor,cf.telefone, nfs.id_fornecedor id_fornecedor,
        cc.razao_social cliente, ct.razao_social transportadora, nfs.valor,
        nfs.frete, nfs.status_fiscal, nfs.anexo_pdf, nfs.nota_fiscal, nfs.volumes, nfs.bloqueado,
        (SELECT transportadora FROM faturamento f where f.id <> nfs.id_faturamento and  f.id_cliente=nfs.id_cliente AND f.transportadora <> 0 ORDER BY f.id DESC LIMIT 1) as ultima_transportadora
        from nota_fiscal_saida nfs
        INNER JOIN colaboradores cf ON (cf.id=nfs.id_fornecedor)
        INNER JOIN colaboradores cc ON (cc.id=nfs.id_cliente)
        INNER JOIN faturamento ON(faturamento.id = nfs.id_faturamento)
        LEFT JOIN colaboradores ct ON (ct.id=nfs.id_transportadora)
        WHERE {$campos} {$ordena} {$limite}";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $linhas = $resultado->fetchAll(PDO::FETCH_ASSOC);
        if ($linhas) {
            return $linhas;
        }
        return ["error" => "Erro ao buscar notas fiscais"];
    }

    public function listaNotasFiscaisFornecedor(int $id_fornecedor, string $filtro, int $status)
    {
        $query = "SELECT nfs.id_faturamento pedido, 
         DATE_FORMAT(nfs.data_cadastro, '%d/%m/%Y %H:%i:%s') AS data_cadastro1,
        nfs.data_cadastro datas,
        cf.razao_social fornecedor,
        cc.razao_social cliente,
        cc.*,
        (SELECT razao_social FROM colaboradores WHERE colaboradores.id = nfs.id_transportadora)as transportadora,
        nfs.volumes,
        nfs.valor,
        nfs.frete,
        nfs.status_fiscal,
        (SELECT campo FROM colaboradores_temp WHERE colaboradores_temp.id_colaborador = nfs.id_cliente order by data_edicao DESC limit 1)campo,
        (SELECT DATEDIFF(MAX(colaboradores_temp.data_edicao),(SELECT data_emissao FROM faturamento WHERE faturamento.id = nfs.id_faturamento)) FROM colaboradores_temp WHERE nfs.id_cliente = colaboradores_temp.id_colaborador)dias_modificacao
        from nota_fiscal_saida nfs
        INNER JOIN colaboradores cf ON (cf.id=nfs.id_fornecedor)
        INNER JOIN colaboradores cc ON (cc.id=nfs.id_cliente)
        WHERE CASE WHEN (nfs.status_fiscal = 1) THEN nfs.volumes > 0  ELSE nfs.volumes > 0 AND nfs.id_transportadora <> 0 END AND nfs.id_fornecedor = {$id_fornecedor}
        ";
        if ($filtro != '') {
            $query .= " AND (nfs.id_faturamento = {intVal($filtro)}
            OR LOWER(cc.razao_social) like LOWER('%{$filtro}%')
            OR nfs.valor = {floatVal($filtro)}";
        }
        if ($status > 0) {
            $query .= " AND status_fiscal = {$status}";
        } else {
            $query .= " AND status_fiscal <= 3";
        }
        $query .= " ORDER BY status_fiscal DESC, nfs.id_faturamento DESC;";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        $linhas = $resultado->fetchAll(PDO::FETCH_ASSOC);
        if ($linhas) {
            return $linhas;
        }
        return ["error" => "Erro ao buscar notas fiscais"];
    }


    public function buscaNotaFiscal(int $id_faturamento, int $id_fornecedor)
    {
        $query = "SELECT nfs.id_faturamento pedido,nfs.id_cliente, 
        DATE_FORMAT(nfs.data_cadastro, '%d/%m/%Y') AS data_cadastro,
        cf.razao_social fornecedor,
        cc.razao_social cliente,
        ct.razao_social transportadora,
        nfs.nota_fiscal,
        (SELECT faturamento.observacao FROM faturamento WHERE faturamento.id=nfs.id_faturamento)observacao,
        (SELECT faturamento.tabela_preco FROM faturamento WHERE faturamento.id=nfs.id_faturamento)as pagamento,
        (SELECT lancamento_financeiro.parcelamento FROM lancamento_financeiro WHERE lancamento_financeiro.pedido_origem=nfs.id_faturamento AND origem='FA') as parcelas,
        DATE_FORMAT(nfs.data_emissao, '%Y-%m-%d') AS data_emissao,
        nfs.anexo_pdf,
        (SELECT faturamento.valor_total FROM faturamento WHERE faturamento.id=nfs.id_faturamento)valor,
        nfs.frete,
        nfs.status_fiscal,
        DATE(NOW()) AS data_emissao_eua,
        CASE WHEN LENGTH(cc.cnpj)=14 THEN cc.cnpj WHEN LENGTH(cc.cpf)=11 THEN cc.cpf ELSE 0 END AS cgc_cliente,
        cc.inscricao inscricao_cliente,
        CONCAT(cc.endereco,', ',cc.numero) AS endereco_cliente,
        cc.complemento complemento_cliente,
        cc.bairro bairro_cliente,
        cc.cep cep_cliente,
        cc.cidade cidade_cliente,
        cc.uf uf_cliente,
        ct.razao_social transportadora,
        ct.cnpj cnpj_transportadora,
        ct.bairro bairro_transportadora,
        ct.cidade cidade_transportadora,
        ct.uf uf_transportadora,
        CONCAT(ct.endereco,', ',ct.numero) AS endereco_transportadora,
        (SELECT faturamento.pares FROM faturamento WHERE faturamento.id = nfs.id_faturamento) pares,
        nfs.peso,
        nfs.volumes,
        cc.telefone AS fone
        from nota_fiscal_saida nfs
        INNER JOIN colaboradores cf ON (cf.id=nfs.id_fornecedor)
        INNER JOIN colaboradores cc ON (cc.id=nfs.id_cliente)
        LEFT OUTER JOIN colaboradores ct ON (ct.id=nfs.id_transportadora)
        WHERE nfs.id_fornecedor = {$id_fornecedor} AND nfs.id_faturamento = {$id_faturamento}
        ORDER BY status_fiscal DESC;";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->query($query);
        return $resultado->fetch(PDO::FETCH_ASSOC);
    }

    // public function buscaNotaFiscalItens(int $id_faturamento, int $id_fornecedor)
    // {
    //     $query = "SELECT fi.nome_tamanho tamanho, fi.preco valor_venda,
    //     (SELECT p.descricao FROM produtos p WHERE p.id=fi.id_produto) produto
    //     FROM faturamento_item fi 
    //     WHERE fi.id_faturamento={$id_faturamento} 
    //     AND fi.id_fornecedor={$id_fornecedor};";
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     return $resultado->fetchAll(PDO::FETCH_ASSOC);
    // }

    public function anexaPDFNotaFiscal(int $id_faturamento, array $file)
    {
        $extensao = substr($file['pdf']['name'], strripos($file['pdf']['name'], '.'));
        if (strtolower($extensao) == '.pdf') {
            try {
                $s3 = new S3Client(Globals::S3_OPTIONS('FISCAL_PDF'));
            } catch (Exception $e) {
                die("Error " . $e->getMessage());
            }
            $nomePdf = $id_faturamento . '_' . $file['pdf']['name'];
            $pdf = $file['pdf']['tmp_name'];
            try {
                $s3->putObject(
                    [
                        'Bucket' => 'mobilestock-pdf',
                        'Key' => $nomePdf,
                        'SourceFile' => $pdf,
                    ]
                );
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            return true;
        }
        return;
    }

    public function atualizaNotaFiscalFornecedor(int $id_faturamento, int $id_fornecedor, string $data_emissao, int $numero, array $pdf)
    {
        $query = "UPDATE nota_fiscal_saida SET";
        if ($numero > 0) {
            $query .= " nota_fiscal={$numero},  ";
        }
        if (sizeof($pdf) > 0) {
            $query .= " anexo_pdf='https://mobilestock-pdf.s3-sa-east-1.amazonaws.com/{$id_faturamento}_{$pdf['pdf']['name']}',";
        }
        $query .= "status_fiscal = 
                                CASE WHEN status_fiscal = 3 
                                    THEN 4 
                                        ELSE 5 
                                END,";
        if ($data_emissao != '') {
            date_default_timezone_set('America/Sao_Paulo');
            $data_emissao = date('Y-m-d', strtotime($data_emissao));
            $query .= " data_emissao=NOW(),";
        }
        $query .= "id_faturamento={$id_faturamento} WHERE id_fornecedor={$id_fornecedor} AND id_faturamento={$id_faturamento};";
        $conexao = Conexao::criarConexao();
        $conexao->exec($query);


        $sql = "UPDATE faturamento 
        SET faturamento.nota_fiscal = 
            (SELECT nota_fiscal_saida.nota_fiscal 
                FROM nota_fiscal_saida 
            WHERE nota_fiscal_saida.id_faturamento = faturamento.id AND nota_fiscal_saida.anexo_pdf IS NOT NULL LIMIT 1) 
        WHERE id = {$id_faturamento}";
        if ($conexao->exec($sql))
            return true;
        return ["error" => "Erro ao inserir informacao da nota fiscal"];
    }

    public function encaminharNotaFiscal(int $id_faturamento, int $id_fornecedor)
    {
        $nota = $this->buscaNotaFiscal($id_faturamento, $id_fornecedor);
        if ($nota['anexo_pdf'] == null) {
            return ['error' => 'Não existe nota fiscal anexada. Anexe pelo botão DETALHES.'];
        }
        if ($nota['data_emissao'] == null) {
            return ['error' => 'Nota fiscal com data de emissão em branco. Informe pelo botão DETALHES.'];
        }
        if ($nota['nota_fiscal'] == 0) {
            return ['error' => 'Número da nota fiscal em branco. Informe pelo botão DETALHES.'];
        }
        $query = "UPDATE nota_fiscal_saida SET status_fiscal = 4 WHERE id_faturamento={$id_faturamento} AND id_fornecedor={$id_fornecedor};";
        $conexao = Conexao::criarConexao();
        if ($conexao->exec($query))
            return true;
        return ["error" => "Erro ao atualizar status da nota fiscal"];
    }

    // public function buscarNotasFiscaisParaComissao(array $post)
    // {
    //     extract($post);

    //     $filtro = '';

    //     if ($fornecedor != '') {
    //         $filtro .= ' AND c.razao_social LIKE "%' . $fornecedor . '%"';
    //     }

    //     if ($mes != '') {
    //         $filtro .= ' AND MONTH(f.data_fechamento)="' . $mes . '"';
    //     }

    //     if ($ano != '') {
    //         $filtro .= ' AND YEAR(f.data_fechamento)="' . $ano . '"';
    //     }

    //     $query = "SELECT (SUM(fi.valor_total)) valor_total,
    //     fi.id_fornecedor, c.razao_social fornecedor, c.perc_comissao, MONTH(f.data_fechamento) mes, YEAR(f.data_fechamento) ano
    //     FROM faturamento_item fi
    //     INNER JOIN faturamento f ON (fi.id_faturamento = f.id)
    //     INNER JOIN produtos p ON (p.id=fi.id_produto)
    //     INNER JOIN colaboradores c ON (c.id=fi.id_fornecedor)
    //     WHERE p.consignado = 1
    //     AND fi.id_fornecedor > 0
    //     {$filtro}
    //     GROUP BY YEAR(f.data_fechamento), MONTH(f.data_fechamento), fi.id_fornecedor
    //     HAVING (SUM(fi.valor_total))>0;";
    //     $conexao = Conexao::criarConexao();
    //     $resultado = $conexao->query($query);
    //     $linhas = $resultado->fetchAll(PDO::FETCH_ASSOC);

    //     $query = "SELECT * FROM configuracoes;";
    //     $resultado = $conexao->query($query);
    //     $config = $resultado->fetch(PDO::FETCH_ASSOC);

    //     if ($config['tipo_comissao'] == 'G') {
    //         $comissao = $config['percentual_comissao'];
    //         foreach ($linhas as $key => $l) {
    //             $linhas[$key]['comissao'] = floatVal($comissao);
    //             $linhas[$key]['valor_comissao'] = floatVal($l['valor_total'] * ($comissao / 100));
    //         }
    //         return $linhas;
    //     }
    // }


    public function atualizaStatusNotaFiscal(array $param)
    {
        extract($param);
        $sql = "UPDATE nota_fiscal_saida SET status_fiscal={$status} WHERE id_faturamento={$id_faturamento} AND id_fornecedor = {$id_fornecedor} ";
        $conexao = Conexao::criarConexao();
        return $conexao->exec($sql);
    }


    public function finalizaNotas(int $id, int $id_fornecedor)
    {
        $query = "UPDATE nota_fiscal_saida SET status_fiscal = 5 WHERE id_faturamento={$id} AND id_fornecedor = {$id_fornecedor}";
        $conexao = Conexao::criarConexao();
        $conexao->exec($query);
        $conferidor = idUsuarioLogado();
        //SOMETE O APP PODE MUDAR AS SITUACOES DE ENTREGUE, EXPEDIDO, CONFERIDO OU SEPARADO
        //$sql = "UPDATE faturamento SET entregue=1, expedido = 1, data_expedicao = NOW(), data_entrega = NOW(), id_expedidor ={$conferidor}  WHERE id={$id}";
        //$conexao = Conexao::criarConexao();
        //$conexao->exec($sql);
        $buscaCliente = "SELECT id_cliente,anexo_pdf FROM nota_fiscal_saida WHERE id_faturamento={$id} AND anexo_pdf <> ''  LIMIT 1";
        $conexao = Conexao::criarConexao();
        $stmt = $conexao->prepare($buscaCliente);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_cliente = $resultado['id_cliente'];
        $anexo = $resultado['anexo_pdf'];
        $consulta = "INSERT INTO notificacoes(id_cliente, data_evento, mensagem, recebida, tipo_frete, tipo_mensagem) 
                        VALUES ({$id_cliente},NOW(),'{$anexo}', 0, 0,'F')";
        $conexao = Conexao::criarConexao();
        return $conexao->exec($consulta);
    }
    public function buscarFaturamentosComissao(int $id_fornecedor, int $mes, int $ano)
    {
        $query = "SELECT
            faturamento.id,
            faturamento.data_emissao,
            faturamento.data_fechamento,
            faturamento.valor_produtos,
            faturamento.pares,
            faturamento_item.nome_tamanho tamanho,
            faturamento_item.preco,
            (
                SELECT colaboradores.razao_social
                FROM colaboradores
                WHERE colaboradores.id = faturamento.id_cliente
            ) cliente,
            (
                SELECT colaboradores.razao_social
                FROM colaboradores
                WHERE colaboradores.id = faturamento_item.id_fornecedor
            ) fornecedor,
            (
                SELECT colaboradores.razao_social
                FROM colaboradores
                WHERE colaboradores.id = faturamento.transportadora
            ) transportadora,
            (
                SELECT produtos.descricao
                FROM produtos
                WHERE produtos.id = faturamento_item.id_produto
            ) referencia
        FROM faturamento
        INNER JOIN faturamento_item ON faturamento_item.id_faturamento = faturamento.id
        WHERE faturamento_item.id_fornecedor = :id_fornecedor
            AND MONTH(faturamento.data_fechamento) = :mes
            AND YEAR(faturamento.data_fechamento) = :ano
        GROUP BY faturamento.id;";
        $conexao = Conexao::criarConexao();
        $resultado = $conexao->prepare($query);
        $resultado->bindValue(":id_fornecedor", $id_fornecedor, PDO::PARAM_INT);
        $resultado->bindValue(":mes", $mes, PDO::PARAM_STR);
        $resultado->bindValue(":ano", $ano, PDO::PARAM_STR);
        $resultado->execute();
        $result = $resultado->fetchAll(PDO::FETCH_ASSOC);

        $faturamentos = [];
        foreach ($result as $key => $r) {
            $faturamentos['id'] = $r['id'];
            $faturamentos['data_emissao'] = $r['data_emissao'];
            $faturamentos['data_fechamento'] = $r['data_fechamento'];
            $faturamentos['valor_produtos'] = $r['valor_produtos'];
            $faturamentos['pares'] = $r['pares'];
            $faturamentos['cliente'] = $r['cliente'];
            $faturamentos['produtos'] = $r;
        }

        if (sizeof($faturamentos) > 0) {
            return [$faturamentos];
        }
        return [];
    }

    public function atualizaVolumesNotasFiscais(int $id_faturamento, int $volumes)
    {
        $query = "UPDATE nota_fiscal_saida SET volumes = {$volumes} WHERE id_faturamento={$id_faturamento};";
        $conexao = Conexao::criarConexao();
        return $conexao->exec($query);
    }
    public function alteracaoDadosCliente(int $id_cliente, int $id_faturamento)
    {
        $query = "SELECT *,DATEDIFF(colaboradores_temp.data_edicao,(SELECT data_emissao FROM faturamento WHERE faturamento.id = {$id_faturamento})) as dias FROM colaboradores_temp WHERE colaboradores_temp.id_colaborador = {$id_cliente}";
        $conexao = Conexao::criarConexao();
        $stm = $conexao->query($query);
        return $stm->fetch(PDO::FETCH_ASSOC);
    }
}
*/