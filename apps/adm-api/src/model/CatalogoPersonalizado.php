<?php

namespace MobileStock\model;

use Exception;
use MobileStock\helper\Validador;

/**
 * @property int $id
 * @property int $id_colaborador
 * @property string $nome
 * @property array $produtos
 * @property array $plataformas_filtros
 * @property string $data_criacao
 * @property string $data_atualizacao
 *
 * @deprecated
 * @see Usar: MobileStock\model\CatalogoPersonalizadoModel
 */
class CatalogoPersonalizado
{
    public string $nome_tabela = 'catalogo_personalizado';

    public function __set($chave, $valor)
    {
        if (in_array($chave, ['produtos', 'plataformas_filtros'])) {
            $valorAux = $valor;
            if (!is_array($valorAux)) {
                throw new Exception("O campo $chave deve ser um array ou uma string de array válida.");
            }
            $tipoValidacao =
                $chave === 'produtos' ? Validador::NUMERO : Validador::ENUM(Origem::MS, Origem::ML, Origem::MED);
            foreach ($valorAux as $item) {
                Validador::validar(
                    [$chave => $item],
                    [
                        $chave => [Validador::OBRIGATORIO, $tipoValidacao],
                    ]
                );
            }
        }
        $this->$chave = $valor;
    }

    public function extrair()
    {
        $data = get_object_vars($this);
        if (isset($data['ativo'])) {
            $data['ativo'] = (int) $data['ativo'];
        }
        if (isset($data['produtos'])) {
            $data['produtos'] = json_encode($data['produtos']);
        }
        if (isset($data['plataformas_filtros'])) {
            $data['plataformas_filtros'] = json_encode($data['plataformas_filtros']);
        }
        return $data;
    }
}
