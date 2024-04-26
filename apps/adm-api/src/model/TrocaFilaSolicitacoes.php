<?php

namespace MobileStock\model;

use Exception;

/**
 * @property int $id
 * @property int $id_cliente
 * @property int $id_produto
 * @property string $nome_tamanho
 * @property string $uuid_produto
 * @property string $situacao
 * @property string $descricao_defeito
 * @property string $motivo_reprovacao_seller
 * @property string $motivo_reprovacao_disputa
 * @property string $motivo_reprovacao_foto
 * @property string $foto1
 * @property string $foto2
 * @property string $foto3
 * @property string $foto4
 * @property string $foto5
 * @property string $foto6
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @deprecated
 */

class TrocaFilaSolicitacoes
{
    public string $nome_tabela = 'troca_fila_solicitacoes';

    public function __set($atrib, $value)
    {
        if (!$value) {
            $this->$atrib = '';
        }
        if (
            $atrib === 'situacao' &&
            !in_array($value, [
                'APROVADO',
                'CANCELADO_PELO_CLIENTE',
                'EM_DISPUTA',
                'SOLICITACAO_PENDENTE',
                'PERIODO_DE_LEVAR_AO_PONTO_EXPIRADO',
                'REPROVADO',
                'REPROVADA_NA_DISPUTA',
                'ITEM_TROCADO',
                'REPROVADA_POR_FOTO',
                'PENDENTE_FOTO',
            ])
        ) {
            throw new Exception('Situação Inválida!');
        }
        $this->$atrib = $value;
    }

    public function __get($atrib)
    {
        return $this->$atrib ?? '';
    }

    public function extrair(): array
    {
        return [
            'id' => $this->id ?? '',
            'id_cliente' => $this->id_cliente ?? '',
            'id_produto' => $this->id_produto ?? '',
            'nome_tamanho' => $this->nome_tamanho ?? '',
            'uuid_produto' => $this->uuid_produto ?? '',
            'situacao' => $this->situacao ?? '',
            'descricao_defeito' => $this->descricao_defeito ?? '',
            'motivo_reprovacao_seller' => $this->motivo_reprovacao_seller ?? '',
            'motivo_reprovacao_disputa' => $this->motivo_reprovacao_disputa ?? '',
            'motivo_reprovacao_foto' => $this->motivo_reprovacao_foto ?? '',
            'foto1' => $this->foto1 ?? '',
            'foto2' => $this->foto2 ?? '',
            'foto3' => $this->foto3 ?? '',
            'foto4' => $this->foto4 ?? '',
            'foto5' => $this->foto5 ?? '',
            'foto6' => $this->foto6 ?? '',
            'data_criacao' => $this->data_criacao ?? '',
            'data_atualizacao' => $this->data_atualizacao ?? '',
        ];
    }
}
