<?php
// namespace MobileStock\service\Marketplace;

// use PDO;

// class MarketplaceService
// {
//     public static function consultaTotais(PDO $conexo, int $idFornecedor,string $data_inicio,string $data_fim)
//     {
      
//         $consulta = $conexo->prepare("
//             SELECT 'seller' nome, 
//             c.razao_social valor, 
//             c.id qtd FROM colaboradores c
//             WHERE c.id = :idFornecedor

//             UNION ALL

//             SELECT 'vendido' nome,
//                 COALESCE(SUM(lf.valor),0) valor,
//                 COUNT(lf.id) qtd 
//             FROM lancamento_financeiro lf
//             WHERE lf.id_colaborador = :idFornecedor
//             AND lf.origem in ('SP','SC')
//             AND DATE(lf.data_emissao) >= :dataInicio
//             AND DATE(lf.data_emissao) <= :dataFim

//             UNION ALL

//             SELECT 'recebido' nome,
//                 COALESCE(SUM(lf.valor),0) valor,
//                 COUNT(lf.id) qtd 
//             FROM lancamento_financeiro lf
//             WHERE lf.id_colaborador = :idFornecedor
//             AND lf.situacao=2 AND lf.origem in ('SP','SC')
//             AND DATE(lf.data_emissao) >= :dataInicio
//             AND DATE(lf.data_emissao) <= :dataFim

//             UNION ALL 

//             SELECT 'recebido_pago' nome,
//                 COALESCE(SUM(lfr.valor),0) valor,
//                 COUNT(lfr.id) qtd 
//             FROM lancamentos_financeiros_recebiveis lfr
//             INNER JOIN lancamento_financeiro lf ON lf.id=lfr.id_lancamento
//             WHERE lfr.id_recebedor = :idFornecedor
//             AND lf.situacao=2 AND lfr.situacao = 'PA' AND lf.origem in ('SP','SC')
//             AND DATE(lf.data_emissao) >= :dataInicio
//             AND DATE(lf.data_emissao) <= :dataFim

//             UNION ALL 

//             SELECT 'recebido_futuro' nome,
//                 COALESCE(SUM(lfr.valor),0) valor,
//                 COUNT(lfr.id) qtd 
//             FROM lancamentos_financeiros_recebiveis lfr
//             INNER JOIN lancamento_financeiro lf ON lf.id=lfr.id_lancamento
//             WHERE lfr.id_recebedor = :idFornecedor
//             AND lf.situacao=2 AND lfr.situacao = 'PE' AND lf.origem in ('SP','SC')
//             AND DATE(lf.data_emissao) >= :dataInicio
//             AND DATE(lf.data_emissao) <= :dataFim
            
//             UNION ALL 

//             SELECT 'estoque_foto' nome,
//                 COUNT(produtos_separacao_fotos.id_produto) valor,
//                  COUNT(produtos_separacao_fotos.id_produto) qtd  
//             FROM produtos 
//             INNER JOIN produtos_separacao_fotos  ON(produtos_separacao_fotos.id_produto = produtos.id)
//             WHERE produtos.id_fornecedor = :idFornecedor

//             UNION ALL 

//             SELECT 'receber' nome,
//                 COALESCE(SUM(lf.valor),0) valor,
//                 COUNT(lf.id) qtd 
//             FROM lancamento_financeiro lf
//             WHERE lf.id_colaborador = :idFornecedor
//             AND lf.situacao=1 AND lf.origem in ('SP','SC','AU')
//             AND DATE(lf.data_emissao) >= :dataInicio
//             AND DATE(lf.data_emissao) <= :dataFim

//             UNION ALL 

//             SELECT 'estoque' nome,
//                 SUM((estoque_grade.estoque) * produtos.valor_custo_produto) valor,
//                 SUM(estoque_grade.estoque) qtd
//             FROM estoque_grade
//                 INNER JOIN produtos ON produtos.id =  estoque_grade.id_produto
//             WHERE 
//                 produtos.id_fornecedor = :idFornecedor");
                
//         $consulta->bindParam(':idFornecedor', $idFornecedor, PDO::PARAM_INT);
//         $consulta->bindParam(':dataInicio', $data_inicio, PDO::PARAM_STR);
//         $consulta->bindParam(':dataFim', $data_fim, PDO::PARAM_STR);
//         $consulta->execute();
//         $resposta = $consulta->fetchAll(PDO::FETCH_ASSOC); 
//         return $resposta;
//     }
// }