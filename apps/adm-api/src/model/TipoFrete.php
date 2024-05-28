<?php

namespace MobileStock\model;

class TipoFrete
{
    public $nome_tabela = 'tipo_frete';
    private int $id;
    private ?string $nome;
    private ?string $titulo;
    private ?string $mensagem;
    private ?string $mensagem_cliente;
    private int $id_colaborador;
    /**
     * @deprecated
     */
    private string $categoria;
    private ?string $previsao_entrega;
    private ?string $latitude;
    private ?string $longitude;
    private ?string $foto;
    private string $horario_de_funcionamento;
    private ?string $emitir_nota_fiscal = '0';
    private string $tipo_ponto = 'PP';
    /**
     * @deprecated
     */
    private ?string $percentual_comissao = '0';
    private ?int $id_usuario;

    public const ID_TIPO_FRETE_ENTREGA_CLIENTE = '1,2,3,4';
    public const ID_COLABORADOR_TIPO_FRETE_ENTREGA_CLIENTE = '38621,32257,32254,32262';
    public const ID_COLABORADOR_CENTRAL = 32254;
    public const ID_COLABORADOR_TRANSPORTADORA = 32257;
    public const ID_TIPO_FRETE_TRANSPORTADORA = 2;

    /**
     * @deprecated
     * @issue: https://github.com/mobilestock/backend/issues/251
     */
    public const LISTA_IDS_COLABORADORES_MOBILE_ENTREGAS = [30726, 79563];

    public function __set($atrib, $value)
    {
        $this->$atrib = $value;
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

    public function __construct()
    {
        $this->id = 0;
    }

    public function extrair(): array
    {
        $extrair = get_object_vars($this);

        return $extrair;
    }

    public static function converteCategoria(string $situacao): string
    {
        switch (true) {
            case in_array($situacao, ['CR', 'FL', 'PE']):
                $categoria = 'PENDENTE';
                break;
            case in_array($situacao, ['ML', 'MS']):
                $categoria = 'ATIVO';
                break;
            default:
                $categoria = 'SOLICITAR';
                break;
        }

        return $categoria;
    }
}
