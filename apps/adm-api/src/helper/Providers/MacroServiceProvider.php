<?php

namespace MobileStock\helper\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use MobileStock\helper\Validador;

class MacroServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Request::macro('telefone', function (string $key = 'telefone'): string {
            /** @var Request $this */
            $telefone = $this->input($key);
            Validador::validar(
                [$key => $telefone],
                [
                    $key => [Validador::OBRIGATORIO, Validador::TELEFONE],
                ]
            );

            return preg_replace('/[^0-9]/', '', $telefone);
        });
        Str::macro('toUtf8', function (string $texto): string {
            if (env('INSTANCIA_DE_EXECUCAO') === 'CLI') {
                trigger_error('Função sanitizeString não deve ser usada em CLI', E_USER_DEPRECATED);
            }
            setlocale(LC_CTYPE, 'pt_BR.UTF-8');
            $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
            return $texto;
        });
        Str::macro('retornaSigla', function (string $texto): string {
            $retorno = '';
            $listaDePalavras = explode(' ', $texto);

            foreach (
                array_filter($listaDePalavras, fn($item) => !in_array($item, ['de', 'da', 'do', 'das', 'dos']))
                as $palavra
            ) {
                $palavra = mb_strtoupper($palavra);
                $palavra = Str::toUtf8($palavra);
                $palavra = preg_replace('/[^A-Z]/', '', $palavra);
                $retorno .= mb_substr($palavra, 0, 1, 'UTF-8');
            }

            return $retorno;
        });
        Str::macro('formatarTelefone', function (string $telefone): string {
            $formatado = preg_replace('/[^0-9]/', '', $telefone);
            $formatado = mb_substr($formatado, 0, 11);
            if (mb_strlen($formatado) < 10) {
                return '';
            }
            $encontrado = [];
            preg_match('/^([0-9]{2})([0-9]{4,5})([0-9]{4})$/', $formatado, $encontrado);
            if ($encontrado) {
                return '(' . $encontrado[1] . ') ' . $encontrado[2] . '-' . $encontrado[3];
            }

            return $formatado;
        });
    }
}
