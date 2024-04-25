<?php
// namespace MobileStock\service;

// use Exception;
// use MobileStock\database\Conexao;
// use PDO;

// class AvaliacaoService{
//     public static function insereAvaliacao(int $pedido, int $idCliente, int $idProduto, int $nota, string $comentario, string $foto)
//     {
//         $conexao = Conexao::criarConexao();
//         $sql = "INSERT INTO avaliacao_produtos (id_cliente, id_produto, qualidade, comentario, data_avaliacao, id_faturamento, foto_upload)
//         VALUES ({$idCliente}, {$idProduto}, {$nota}, '{$comentario}', NOW(), {$pedido}, '{$foto}')";
//         $stm = $conexao->prepare($sql);
//         return $stm->execute();
//     }

//     public static function buscaAvaliacao(int $pedido, int $produto)
//     {
//         $conexao = Conexao::criarConexao();
//         $sql = "SELECT * FROM avaliacao_produtos WHERE id_faturamento={$pedido} AND id_produto={$produto};";
//         $stm = $conexao->prepare($sql);
//         $stm->execute();
// 		$resultado = $stm->fetchAll(PDO::FETCH_ASSOC);
// 		return $resultado;
//     }
// }
?>