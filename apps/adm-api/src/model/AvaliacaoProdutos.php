<?php

namespace MobileStock\model;

use Exception;

/**
 * @property int $id
 * @property int $id_cliente
 * @property int $id_produto
 * @property int $qualidade
 * @property int $custo_beneficio
 * @property string $comentario
 * @property string $data_avaliacao
 * @property string $foto_upload
 * @property int $id_faturamento
 * @property int $data_criacao
 */
class AvaliacaoProdutos {

  public function __set($atrib, $value) {
    if ($atrib == 'qualidade' && ($value < 0 || $value > 5)) throw new Exception('Qualidade Inválida!');
    if ($atrib == 'custo_beneficio' && ($value < 0 || $value > 5)) throw new Exception('Custo benefício Inválido!');
    $this->$atrib = $value;
  }

  public function extrair(): array {
    return [
      'id' => $this->id ?? null,
      'id_cliente' => $this->id_cliente ?? null,
      'id_produto' => $this->id_produto ?? null,
      'qualidade' => $this->qualidade ?? 0,
      'custo_beneficio' => $this->custo_beneficio ?? 0,
      'comentario' => $this->comentario ?? null,
      'data_avaliacao' => $this->data_avaliacao ?? null,
      'foto_upload' => $this->foto_upload ?? null,
      'id_faturamento' => $this->id_faturamento ?? null,
      'data_criacao' => $this->data_criacao ?? null
    ];
  }

}