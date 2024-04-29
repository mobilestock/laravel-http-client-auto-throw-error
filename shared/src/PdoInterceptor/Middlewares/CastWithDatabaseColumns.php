<?php

namespace MobileStock\Shared\PdoInterceptor\Middlewares;

use Closure;

class CastWithDatabaseColumns
{
    protected array $columnCache = [];
    protected int $depth = 0;
    protected Closure $stmtCall;
    protected array $termosPrefixoBooleano;

    public function __construct(array $termosPrefixoBooleano)
    {
        $this->termosPrefixoBooleano = $termosPrefixoBooleano;
    }

    public function handle(array $pdoData, callable $next)
    {
        if ($pdoData['stmt_method'] !== 'fetchAll') {
            return $next($pdoData);
        }
        $this->columnCache = [];

        $result = $next($pdoData);
        $this->stmtCall = $pdoData['stmt_call'];

        if (array_is_list($result) && array_key_exists(0, $result) && !is_array($result[0])) {
            $column = ($this->stmtCall)('getColumnMeta', 0)['name'];

            foreach ($result as &$item) {
                [$ignore, $item] = $this->castValue(0, $item, $column);
            }
        } else {
            foreach ($result as &$data) {
                $data = $this->castAssoc($data, null);
                $this->depth = 0;
            }
        }
        $pdoData['result'] = $result;
        return $result;
    }

    protected function castValue(int $key, $value, string $columnName): array
    {
        if (isset($this->columnCache[$columnName])) {
            [$columnName, $castFunction] = $this->columnCache[$columnName];
            $value = $castFunction($value);

            return [$columnName, $value];
        }

        $posicaoPonto = mb_strrpos($columnName, '|', -1);
        $activeColumnName = mb_substr($columnName, $posicaoPonto ? $posicaoPonto + 1 : 0);

        foreach ($this->termosPrefixoBooleano as $prefixo) {
            if (!str_starts_with($activeColumnName, $prefixo . '_')) {
                continue;
            }

            $this->columnCache[$columnName] = [$activeColumnName, 'boolval'];

            return $this->castValue($key, $value, $columnName);
        }

        foreach (
            [
                ['bool', 5, 'boolval'],
                ['int', 4, 'intval'],
                ['float', 6, 'floatval'],
                ['string', 7, 'strval'],
                ['json', 5, [static::class, 'jsonval']],
            ]
            as $alias
        ) {
            if (str_starts_with($activeColumnName, $alias[0] . '_')) {
                $this->columnCache[$columnName] = [mb_substr($activeColumnName, $alias[1]), $alias[2]];

                return $this->castValue($key, $value, $columnName);
            } elseif (str_ends_with($activeColumnName, '_' . $alias[0])) {
                $this->columnCache[$columnName] = [mb_substr($activeColumnName, 0, -$alias[1]), $alias[2]];

                return $this->castValue($key, $value, $columnName);
            }
        }

        if ($this->ehArrayAssociativo($value)) {
            $this->columnCache[$columnName] = [$activeColumnName, fn($value) => $this->castAssoc($value, $columnName)];

            return $this->castValue($key, $value, $columnName);
        } elseif (is_array($value) && array_is_list($value)) {
            $this->columnCache[$columnName] = [
                $activeColumnName,
                fn($value) => is_null($value)
                    ? null
                    : array_map(fn($value) => $this->castAssoc($value, $columnName), $value),
            ];

            return $this->castValue($key, $value, $columnName);
        }

        if ($key === false) {
            $this->columnCache[$columnName] = [$activeColumnName, fn($value) => $value];

            return $this->castValue(false, $value, $columnName);
        }

        if ($columnName !== $activeColumnName) {
            $this->columnCache[$columnName] = [$activeColumnName, fn($value) => $value];

            return $this->castValue($key, $value, $columnName);
        }

        $columnMeta = ($this->stmtCall)('getColumnMeta', $key);
        $columnMeta['native_type'] ??= 'STRING';
        switch ($columnMeta['native_type']) {
            case 'LONG':
            case 'LONGLONG':
            case 'SHORT':
            case 'TINY':
            case 'INT24':
            case 'YEAR':
                $this->columnCache[$columnName] = [$columnName, 'intval'];
                break;
            case 'FLOAT':
            case 'DOUBLE':
            case 'NEWDECIMAL':
                $this->columnCache[$columnName] = [$columnName, 'floatval'];
                break;
            default:
                $this->columnCache[$columnName] = [$columnName, fn($value) => $value];
        }

        return $this->castValue($key, $value, $columnName);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected static function jsonval($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $result = json_decode($value, true);

        if (json_last_error()) {
            return $value;
        }

        return $result;
    }

    /**
     * @param mixed $data
     * @param string|null $columnBase
     * @return mixed
     */
    public function castAssoc($data, ?string $columnBase)
    {
        if (!$this->ehArrayAssociativo($data)) {
            return $data;
        }

        $key = 0;

        if (!is_null($columnBase)) {
            $this->depth++;
            /**
             * @issue: https://github.com/mobilestock/backend/issues/98
             * */
            if ($this->depth > 500) {
                throw new \Exception('Profundidade máxima de 500 atingida');
            }
        }

        for ($i = 0; $i < count($data); $i++) {
            $column = array_keys($data)[$i];
            $value = &$data[$column];

            [$newColumn, $value] = $this->castValue($key, $value, !$columnBase ? $column : "$columnBase|" . $column);

            if (!$columnBase) {
                $key++;
            }
            if ($column !== $newColumn) {
                unset($data[$column]);
                $data[$newColumn] = $value;
                $i--;
                continue;
            }
        }

        return $data;
    }
    /**
     * @param mixed $valor
     * @return bool
     */
    protected function ehArrayAssociativo($valor): bool
    {
        if (!is_array($valor)) {
            return false;
        }

        return !array_is_list($valor);
    }
}
