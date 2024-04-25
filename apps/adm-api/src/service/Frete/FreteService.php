<?php

namespace MobileStock\service\Frete;

use MobileStock\model\Pedido\PedidoItem;

class FreteService
{
    /**
     * @deprecated
     * @issue https://github.com/mobilestock/web/issues/3238
     */
    public const PRODUTO_FRETE = 82044;
    // public $imagem;
    // public $localizacao;
    // public $mensagem;

    //     public function retornaValorBaseDeFrete(PDO $conexao, array $dadosFrete, string $uf): array {
    //         $valor_frete = 0;
    //         $idFrete = intval($dadosFrete['frete'] ?? 0);
    //         $stmt = $conexao->prepare(
    //             "SELECT
    //                 tipo_frete.foto,
    //                 tipo_frete.mensagem,
    //                 tipo_frete.mapa
    //             FROM tipo_frete
    //             WHERE tipo_frete.id = :id_frete"
    //         );
    //         $stmt->bindValue(':id_frete', $idFrete, PDO::PARAM_INT);
    //         $stmt->execute();
    //         $dadosFreteiro = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    // //        if(count($dadosFreteiro) < 1):
    // //            throw new Exception("Erro para identificar tipo de frete", 1);
    // //        endif;
    //         switch ($idFrete) {
    //             case 1:
    //                 $valor_frete = 0;
    //                 $this->imagem = $dadosFreteiro['foto'];
    //                 $this->mensagem = $dadosFreteiro['mensagem'];
    //                 $this->localizacao = $dadosFreteiro['mapa'];
    //                 break;
    //             case 2:
    //                 $resultadoValorFrete = $conexao->query("SELECT frete_estado.* FROM frete_estado WHERE LOWER(frete_estado.estado) LIKE LOWER('{$uf}')")->fetch(PDO::FETCH_ASSOC);
    //                 $valor_frete = (float) $resultadoValorFrete['valor_frete'];
    //                 $this->imagem = $dadosFreteiro['foto'];
    //                 $this->mensagem = str_replace("[VALOR]", number_format($valor_frete, 2, ',', ''), $dadosFreteiro['mensagem']);
    //                 $this->localizacao = $dadosFreteiro['mapa'];
    //                 break;
    //             case 3:
    //                 $valor_frete = 0;
    //                 $this->imagem = $dadosFreteiro['foto'];
    //                 $this->mensagem = $dadosFreteiro['mensagem'];
    //                 $this->localizacao = $dadosFreteiro['mapa'];
    //                 break;
    //             case 4:
    //                 $valor_frete = floatval(10);
    //                 $this->imagem = $dadosFreteiro['foto'];
    //                 $this->mensagem = $dadosFreteiro['mensagem'] . number_format($valor_frete, 2, ',', '');
    //                 $this->localizacao = $dadosFreteiro['mapa'];
    //                 break;
    //             // case 5:
    //             //     $valor_frete = 0;
    //             //     $resultado = $conexao->query("SELECT tipo_frete.* FROM tipo_frete WHERE LOWER(tipo_frete.nome) LIKE LOWER('Retirar BH')")->fetch(PDO::FETCH_ASSOC);
    //             //     $this->imagem = $resultado['foto'];
    //             //     $this->mensagem = $resultado['mensagem'];
    //             //     $this->localizacao = $resultado['mapa'];
    //             //     break;
    //             // case 6:
    //             //     $valor_frete = 0;
    //             //     $resultado = $conexao->query("SELECT tipo_frete.* FROM tipo_frete WHERE LOWER(tipo_frete.nome) LIKE LOWER('Divinopolis')")->fetch(PDO::FETCH_ASSOC);
    //             //     $this->imagem = $resultado['foto'];
    //             //     $this->mensagem = $resultado['mensagem'];
    //             //     $this->localizacao = $resultado['mapa'];
    //             //     break;
    //             // case 7:
    //             //     $valor_frete = 0;
    //             //     $resultado = $conexao->query("SELECT tipo_frete.* FROM tipo_frete WHERE LOWER(tipo_frete.nome) LIKE LOWER('Governador')")->fetch(PDO::FETCH_ASSOC);
    //             //     $this->imagem = $resultado['foto'];
    //             //     $this->mensagem = $resultado['mensagem'];
    //             //     $this->localizacao = $resultado['mapa'];
    //             //     break;
    //             // case 8:
    //             //     $valor_frete = 0;
    //             //     $resultado = $conexao->query("SELECT tipo_frete.* FROM tipo_frete WHERE LOWER(tipo_frete.nome) LIKE LOWER('Contagem')")->fetch(PDO::FETCH_ASSOC);
    //             //     $this->imagem = $resultado['foto'];
    //             //     $this->mensagem = $resultado['mensagem'];
    //             //     $this->localizacao = $resultado['mapa'];
    //             //     break;
    //             default:
    //                 $valor_frete = 0;
    //                 $this->imagem = $dadosFreteiro['foto'] ?? '';
    //                 $this->mensagem = $dadosFreteiro['mensagem'] ?? '';
    //                 $this->localizacao = $dadosFreteiro['mapa'] ?? '';
    //                 break;
    //         }

    //         return [
    //                 'valor_frete' => $valor_frete,
    //                 'texto' => $this->mensagem,
    //                 'localizacao' => $this->localizacao,
    //                 'imagem' => $this->imagem
    //             ];
    //    }

    public static function calculaValorFrete(
        int $qtdItensNaoExpedidos,
        int $qtdProdutos,
        float $valorFrete,
        float $valorAdicional
    ): float {
        $qtdMaximaProdutos = PedidoItem::QUANTIDADE_MAXIMA_ATE_ADICIONAL_FRETE;

        $qtdTotalProdutos = $qtdItensNaoExpedidos + $qtdProdutos;
        $qtdFreteAdicional = max(0, $qtdTotalProdutos - $qtdMaximaProdutos);
        $valorFrete +=
            ($qtdItensNaoExpedidos >= $qtdMaximaProdutos ? $qtdProdutos : $qtdFreteAdicional) * $valorAdicional;

        return $valorFrete;
    }
}
