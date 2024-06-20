<?php

namespace MobileStock\model;

use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Validador;

/**
 * @deprecated
 * @see Usar: MobileStock\model\ProdutoModel
 */
class Produto implements \JsonSerializable
{
    const TIPO_GRADE_PRODUTO = 1;
    const TIPO_GRADE_ROUPA = 2;

    public $nome_tabela = 'produtos';
    private $id;
    private $descricao;
    private $usuario;
    private $id_fornecedor;
    private $bloqueado;
    private $data_entrada;
    private $id_linha;
    private $valor_custo_produto_fornecedor;
    private $valor_custo_produto;
    private $destaque;
    private $nome_comercial;
    private string $forma;
    private ?string $embalagem;
    private $tipo_grade;
    private $sexo;
    private $outras_informacoes;
    private $cores;
    private $permitido_reposicao;
    private string $data_primeira_entrada;
    private string $data_alteracao;
    private $fora_de_linha;

    public function __construct(
        string $descricao,
        int $usuario,
        int $id_fornecedor,
        int $id_linha,
        float $valor_custo_produto,
        string $nome_comercial,
        int $tipo_grade,
        ?int $id = null
    ) {
        $this->descricao = $descricao;
        $this->usuario = $usuario;
        $this->id_fornecedor = $id_fornecedor;
        $this->id_linha = $id_linha;
        $this->valor_custo_produto_fornecedor = 0;
        $this->valor_custo_produto = $valor_custo_produto;
        $this->destaque = false;
        $this->nome_comercial = $nome_comercial;
        $this->tipo_grade = $tipo_grade;
        $this->id = 0;
        $this->bloqueado = 0;

        if ($id) {
            $this->setId($id);
            return;
        }

        $this->data_alteracao = date('Y-m-d H:i:s');
    }
    public function __set($campo, $valor)
    {
        if ($campo === 'forma') {
            Validador::validar(
                ['forma' => $valor],
                [
                    'forma' => [Validador::ENUM('PEQUENA', 'NORMAL', 'GRANDE')],
                ]
            );
        }
        $this->$campo = $valor;
    }

    public function getSexo(): string
    {
        return $this->sexo;
    }

    public function setSexo(string $sexo): self
    {
        $this->sexo = $sexo;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function setDescricao(string $descricao): self
    {
        $this->descricao = $descricao;
        return $this;
    }

    public function getUsuario(): int
    {
        return $this->usuario;
    }

    public function setUsuario(int $usuario): self
    {
        $this->usuario = $usuario;
        return $this;
    }

    public function getIdFornecedor(): int
    {
        return $this->id_fornecedor;
    }

    public function setIdFornecedor(int $id_fornecedor): self
    {
        $this->id_fornecedor = $id_fornecedor;
        return $this;
    }

    public function getBloqueado(): bool
    {
        return $this->bloqueado;
    }

    public function setBloqueado(bool $bloqueado): self
    {
        if (!$this->id) {
            return $this;
        }

        $this->bloqueado = $bloqueado;
        return $this;
    }

    public function getDataEntrada(): string
    {
        return $this->data_entrada;
    }

    public function setDataEntrada(string $data_entrada): self
    {
        $this->data_entrada = $data_entrada;
        return $this;
    }

    public function getIdLinha(): int
    {
        return $this->id_linha;
    }

    public function setIdLinha(int $id_linha): self
    {
        $this->id_linha = $id_linha;
        return $this;
    }

    public function getValorCustoProdutoFornecedor(): float
    {
        return $this->valor_custo_produto_fornecedor;
    }

    public function setValorCustoProdutoFornecedor(float $valor_custo_produto_fornecedor): self
    {
        $this->valor_custo_produto_fornecedor = $valor_custo_produto_fornecedor;
        return $this;
    }

    public function getValorCustoProduto(): float
    {
        return $this->valor_custo_produto;
    }

    public function setValorCustoProduto(float $valor_custo_produto): self
    {
        $this->valor_custo_produto = $valor_custo_produto;
        return $this;
    }

    public function getDestaque(): bool
    {
        return $this->destaque;
    }

    public function setDestaque(bool $destaque): self
    {
        $this->destaque = $destaque;
        return $this;
    }

    public function getNomeComercial(): string
    {
        return $this->nome_comercial;
    }

    public function setNomeComercial(string $nome_comercial): self
    {
        $this->nome_comercial = $nome_comercial;
        return $this;
    }

    public function getForma(): string
    {
        return $this->forma;
    }

    public function setForma(string $forma): self
    {
        $this->forma = $forma;
        return $this;
    }

    public function getEmbalagem(): string
    {
        return $this->embalagem;
    }

    public function setEmbalagem(string $embalagem): self
    {
        $this->embalagem = $embalagem;
        return $this;
    }

    public function getTipoGrade(): int
    {
        return $this->tipo_grade;
    }

    public function setTipoGrade(int $tipo_grade)
    {
        $this->tipo_grade = $tipo_grade;
        return $this;
    }

    public function getOutrasInformacoes(): string
    {
        return $this->outras_informacoes;
    }

    public function setOutrasInformacoes(string $outras_informacoes): self
    {
        $this->outras_informacoes = $outras_informacoes;
        return $this;
    }

    public function getCores(): array
    {
        return $this->cores;
    }

    public function setCores(array $cores): self
    {
        $this->cores = $cores;
        return $this;
    }

    public function setForaDeLinha(bool $valor)
    {
        $this->fora_de_linha = $valor;
        return $this;
    }

    public function getForaDeLinha()
    {
        return $this->fora_de_linha;
    }

    public function setPermissaoReposicao(int $permissaoReposicao): self
    {
        $this->permitido_reposicao = $permissaoReposicao;
        return $this;
    }

    public function getPermissaoReposicao(): string
    {
        return $this->permitido_reposicao;
    }

    public function getDataPrimeiraEntrada(): string
    {
        return $this->data_primeira_entrada;
    }

    public function setDataPrimeiraEntrada(string $data_primeira_entrada): self
    {
        $this->data_primeira_entrada = $data_primeira_entrada;
        return $this;
    }

    public function getDataAlteracao(): string
    {
        return $this->data_alteracao;
    }

    public function setDataAlteracao(string $data_alteracao): self
    {
        $this->data_alteracao = $data_alteracao;
        return $this;
    }

    public static function hidratar(array $dados): self
    {
        $reflectClass = new \ReflectionClass(self::class);
        $produto = $reflectClass->newInstanceWithoutConstructor();
        foreach ($dados as $key => $item) {
            $listaObj = array_keys(get_object_vars($produto));
            // Argumentos inválidos
            if (!in_array($key, $listaObj)) {
                throw new \InvalidArgumentException('Argumento ' . $key . ' inválido');
            }
            $conversorStrings = new ConversorStrings([$key]);
            $metodo = $conversorStrings->converteSnakeCaseParaCamelCase('set')[0];

            $produto->$metodo($item);
        }

        return $produto;
    }

    public function extrair(): array
    {
        $dadosBanco = [
            'id' => (int) $this->id,
            'descricao' => (string) $this->descricao,
            'usuario' => (int) $this->usuario,
            'id_fornecedor' => (int) $this->id_fornecedor,
            'bloqueado' => (int) $this->bloqueado,
            'id_linha' => (int) $this->id_linha,
            'valor_custo_produto_fornecedor' => (float) $this->valor_custo_produto_fornecedor,
            'valor_custo_produto' => (float) $this->valor_custo_produto,
            'destaque' => (int) $this->destaque,
            'nome_comercial' => (string) $this->nome_comercial,
            'forma' => $this->forma,
            'tipo_grade' => (int) $this->tipo_grade,
            'sexo' => (string) $this->sexo,
            'cores' => implode(' ', $this->cores),
            'fora_de_linha' => (int) $this->fora_de_linha,
            'permitido_reposicao' => (int) $this->permitido_reposicao,
        ];
        if (!empty($this->embalagem)) {
            $dadosBanco['embalagem'] = $this->embalagem;
        }
        if (!empty($this->outras_informacoes)) {
            $dadosBanco['outras_informacoes'] = $this->outras_informacoes;
        }

        if (!$this->id) {
            $dadosBanco['data_alteracao'] = date('Y-m-d H:i:s');
        }
        return $dadosBanco;
    }

    public function jsonSerialize()
    {
        $objectVars = get_object_vars($this);
        unset($objectVars['nome_tabela']);
        return $objectVars;
    }
}
