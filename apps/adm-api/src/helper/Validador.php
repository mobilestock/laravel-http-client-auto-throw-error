<?php

namespace MobileStock\helper;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

if ($_ENV['AMBIENTE'] === 'producao') {
    error_reporting(E_USER_NOTICE);
}

abstract class Validador
{
    // ------------------- FILTROS DE VALIDACAO ------------------- \\
    const OBRIGATORIO = [
        'metodo' => 'validacaoObrigatoria',
        'mensagem' => 'é obrigatório'
    ];

    const EMAIL = [
        'metodo' => 'validacaoEmail',
        'mensagem' => 'deve ser um e-mail válido'
    ];

    const SANIZAR = [
        'metodo' => 'validacaoSanizar',
        'mensagem' => 'está inválido'
    ];

    const STRING = [
        'metodo' => 'validacaoString',
        'mensagem' => 'deve conter apenas letras'
    ];
    const TELEFONE = [
        'metodo'=> 'validacaoTelefone',
        'mensagem' => 'deve ser um telefone válido'
    ];

    
    const NUMERO = [
        'metodo' => 'validacaoNumero',
        'mensagem' => 'deve conter apenas números'
    ];

    const DATA = [
        'metodo' => 'validacaoData',
        'mensagem' => 'deve ser uma data válida'
    ];

    const DATA_INICIO =[
        'metodo' => 'validacaoData_inicio',
        'mensagem' => 'deve ser a data de origem'
    ];

    const CPF = [
        'metodo' => 'validacaoCpf',
        'mensagem' => 'deve ser um CPF válido'
    ];

    const CEP = [
        'metodo' => 'validacaoCep',
        'mensagem' => 'deve ser um CEP válido'
    ];

    const CNPJ = [
        'metodo' => 'validacaoCnpj',
        'mensagem' => 'deve ser um CNPJ válido'
    ];
    /**
     * NÃO UTILIZAR COM ARRAYS.
     */
    const JSON = [
        'metodo' => 'validacaoJson',
        'mensagem' => 'deve ser um JSON válido'
    ];
    const BOOLEANO = [
        'metodo' => 'validacaoBooleano',
        'mensagem' => 'deve ser um "true" ou "false"'
    ];
	const ARRAY = [
		'metodo' => 'validacaoArray',
		'mensagem' => 'deve ser um array'
	];
    const LATITUDE = [
        'metodo' => 'validacaoLatitude',
        'mensagem' => 'deve ser uma latitude válida'
    ];
    const LONGITUDE = [
        'metodo' => 'validacaoLongitude',
        'mensagem' => 'deve ser uma longitude válida'
    ];
    const NAO_NULO = [
        'metodo' => 'validacaoNaoNula',
        'mensagem' => 'é obrigatório'
    ];

    public static function TAMANHO_MINIMO(int $limite): array
    {
        return [
            'metodo' => fn ($value) => (
                is_array($value) && sizeof($value) >= $limite
            ) || (
                is_string($value) && mb_strlen($value) >= $limite
            ),
            'mensagem' => "deve ter no mínimo $limite elementos/caracteres",
        ];
    }
    public static function TAMANHO_MAXIMO(int $limite): array
    {
        return [
            'metodo' => fn ($value) => (
                is_array($value) && sizeof($value) <= $limite
            ) || (
                is_string($value) && mb_strlen($value) <= $limite
            ),
            'mensagem' => "deve ter no máximo $limite elementos/caracteres",
        ];
    }
    public static function ENUM(...$valoresPossiveis): array
    {
        return [
            'metodo' => fn ($value) => in_array($value, $valoresPossiveis),
            'mensagem' => "deveria ser algum valor entre " . implode(
                ',',
                array_map(
                    ConversorArray::mapEnvolvePorString('"'),
                    $valoresPossiveis
                )
            )
        ];
    }
    public static function validacaoNaoNula($value): bool
    {
        return !is_null($value);
    }

    public static function validacaoLongitude($value): bool
    {
        return $value <= 180 && $value > -180;
    }

    public static function validacaoLatitude($value): bool
    {
        return $value <= 90 && $value >= -90;
    }

	public static function validacaoArray($value): bool
	{
		return is_array($value);
	}

	public static function validacaoBooleano($value): bool
    {
        return (is_bool($value) || $value === 'true' || $value === 'false');
    }

    public static function validacaoJson($value): bool
    {
        if ($value === '') {
            return false;
        }

        // TODO: Está ecorrendo uma depreciação aqui
        // Estou suprimindo pois se não o sistema vai quebrar por completo e várias rotas vao quebrar.
        if ($_ENV['AMBIENTE'] === 'producao') {
            @json_decode($value);
        } else {
            json_decode($value);
        }

        if (json_last_error()) {
            return false;
        }

        return true;
    }

    private static function validacaoCpf($value): bool
    {
        $cpf = preg_replace( '/[^0-9]/is', '', $value);

        // Verifica se foi informado todos os digitos corretamente
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    private static function validacaoCep($cep): bool
    {
        return (bool) preg_match('/^\d{5}[- ]\d{3}$|^\d{8}$/', $cep);
    }

    private static function validacaoCnpj($cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/is', '', $cnpj);

        if (mb_strlen($cnpj) != 14) {
            return false;
        }

        $primeiroDigito = 0;
        $segundoDigito = 0;
        $peso = 5;
        for ($i = 0; $i < 12; $i++) {
            $primeiroDigito += $cnpj[$i] * $peso;
            $peso = ($peso == 2) ? 9 : $peso - 1;
        }

        $peso = 6;
        for ($i = 0; $i < 13; $i++) {
            $segundoDigito += $cnpj[$i] * $peso;
            $peso = ($peso == 2) ? 9 : $peso - 1;
        }

        $primeiroDigito %= 11;
        $primeiroDigito = $primeiroDigito < 2 ? 0 : 11 - $primeiroDigito;
        $segundoDigito %= 11;
        $segundoDigito = $segundoDigito < 2 ? 0 : 11 - $segundoDigito;

        return $cnpj[12] == $primeiroDigito && $cnpj[13] == $segundoDigito;
    }

    private static function validacaoData($value): bool
    {

        if(!preg_match('/\d{4}-\d{2}-\d{2}/', $value))
            throw new ValidacaoException('A data deve estar no formato yyyy-mm-dd');

        $data = explode('-' ,$value);
        $mes = $data[1];
        $dia = $data[2];
        $ano = $data[0];

        if (!checkdate($mes, $dia, $ano)) {

            return false;
        };
        return true;
    }

    private static function validacaoNumero($value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return true;
    }

    private static function validacaoTelefone($value): bool
    {
        if(!preg_match("/\(?\d{2}\)?\s?\d{5}\-?\d{4}/", $value)) {
            return false;
        }
        // Essa linha foi comentada para não bloquear usuários com telefones iniciados em "0"
        // if (str_starts_with(preg_replace('/[^0-9]/', '', $value), '0')) {
        //     throw new ValidacaoException('O telefone não pode começar com 0');
        // }
        return true;
    }

    private static function validacaoString($value): bool
    {

    	if(is_null($value)) return false;

        $verificaNumero = preg_replace('/[^0-9]/is','',$value);
        if($verificaNumero){
            return false;
        }

        if (!ctype_alpha($value) && !!$verificaNumero) {

            return false;
        }
        return true;
    }

    private static function validacaoSanizar($value): bool
    {
        if (
            stripos($value, '#') !== false ||
            stripos($value, '@') !== false ||
            stripos($value, '!') !== false |
            stripos($value, '%') !== false ||
            stripos($value, '¨') !== false ||
            stripos($value, '|') !== false ||
            stripos($value, '"') !== false ||
            stripos($value, '&') !== false ||
            stripos($value, ')') !== false ||
            stripos($value, 'DROP') !== false ||
            stripos($value, 'TRUNCATE') !== false ||
            stripos($value, 'EXIT') !== false ||
            stripos($value, 'KILL') !== false ||
            stripos($value, 'SHUTDOWN') !== false ||
            stripos($value, 'DATABASE') !== false ||
            stripos($value, 'TABLE') !== false ||
            stripos($value, '₢') !== false ||
            stripos($value, 'ª') !== false ||
            stripos($value, '¹') !== false ||
            stripos($value, '¬') !== false ||
            stripos($value, 'º') !== false ||
            stripos($value, '²') !== false ||
            stripos($value, '*') !== false ||
            stripos($value, '=') !== false ||
            stripos($value, '§') !== false ||
            stripos($value, '`') !== false ||
            stripos($value, '}') !== false ||
            stripos($value, ']') !== false ||
            stripos($value, '[') !== false ||
            stripos($value, '{') !== false ||
            stripos($value, '\\') !== false ||
            stripos($value, '.') !== false ||
            stripos($value, '°') !== false ||
            stripos($value, ';') !== false ||
            stripos($value, 'ð') !== false ||
            stripos($value, 'Ÿ') !== false ||
            stripos($value, '–') !== false ||
            stripos($value, '¤') !== false ||
            stripos($value, 'ð') !== false ||
            stripos($value, '') !== false ||
            stripos($value, '˜£') !== false ||
            stripos($value, 'ð') !== false ||
            stripos($value, '<') !== false ||
            stripos($value, '>') !== false
        ) {
            return false;
        }
        return true;
    }

    private static function validacaoEmail($value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        return true;
    }

    private static function validacaoObrigatoria($value): bool
    {
        if ($value === null || !$value || $value === '') {
            return false;
        }
        return true;
    }

    /**
     * @param bool|array|callable(mixed): bool|array|numeric|string $condicao
     * @param array $entao
     * @param array|null $senao
     * @return array<callable>
     */
    public static function SE($condicao, array $entao, array $senao = null): array
    {
        return [
            'metodo' => function ($valor, string $indice) use ($condicao, $entao, $senao) {
                if (is_array($condicao) && array_key_exists('metodo', $condicao[0] ?? $condicao)) {
                    try {
                        self::validar(['valor' => $valor], ['valor' => array_is_list($condicao) ? $condicao : [$condicao]]);
                        $condicao = true;
                    } catch (ValidacaoException $e) {
                        $condicao = false;
                    }
                } elseif (is_callable($condicao)) {
                    $condicao = $condicao($valor);

                    if (is_resource($condicao) || is_callable($condicao)) {
                        throw new \InvalidArgumentException('A função de condição deve retornar um valor bool/numeric/string/array');
                    }
                }

                $condicao = (bool)$condicao;

                if ($condicao) {
                    self::validar([$indice => $valor], [$indice => array_is_list($entao) ? $entao : [$entao]]);
                } elseif ($senao) {
                    self::validar([$indice => $valor], [$indice => array_is_list($senao) ? $senao : [$senao]]);
                }
                return true;
            }
        ];
    }

    // ----------------------- PROCESSO DE VALIDAÇÃO -------------------------------- \\

    public static function validar(array $vaiSerValidado, array $regras)
    {
        foreach ($regras as $indice => $regra) {
            if (!is_array($regra)) {
                throw new \InvalidArgumentException('Você deve enviar um array de regras');
            }
            $valor = $vaiSerValidado[$indice] ?? null;
            foreach ($regra as $rule) {
                $metodo = $rule['metodo'];

                if (is_callable($metodo)) {
                    $resultadoValidacao = call_user_func($metodo, $valor, $indice);
                } elseif (!!$metodo) {
                    $resultadoValidacao = static::$metodo($valor, $indice);
                }

                if ($resultadoValidacao === false) {
                    static::quebrarValidacao($indice, $rule['mensagem']);
                }
            }
        }
    }

    private static function quebrarValidacao($indice, string $mensagem)
    {
        $validacaoException = new ValidacaoException('O campo "' . $indice . '" ' . $mensagem);
        $validacaoException->setIndiceInvalido($indice);
        throw $validacaoException;
    }
}