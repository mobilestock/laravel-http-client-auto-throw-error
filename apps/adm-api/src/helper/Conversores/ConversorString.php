<?php

//namespace MobileStock\helper\Conversores;

/**
 * @deprecated esta classe esta depreciada, vamos usar a classe Str do laravel
 */
//class ConversorString
//{
/**
 * @description Retorna a primeira letra de cada palavra do texto, ignorando as palavras ["de", "da", "do"]
 * @param string $texto
 * @deprecated Usar Str::retornaSigla e converter router para o router do laravel
 * @return string
 */
// public static function retornaSiglaDoTexto(string $texto): string
// {
//     $retorno = '';
//     $listaDePalavras = explode(' ', $texto);
//     foreach (
//         array_filter($listaDePalavras, fn($item) => !in_array($item, ['de', 'da', 'do', 'das', 'dos']))
//         as $palavra
//     ) {
//         $palavra = mb_strtoupper($palavra);
//         $palavra = self::sanitizeString($palavra);
//         $palavra = preg_replace('/[^A-Z]/', '', $palavra);
//         $retorno .= mb_substr($palavra, 0, 1, 'UTF-8');
//     }
//     return $retorno;
// }
/**
 * @description Remove acentos e caracteres especiais do texto
 * @deprecated Usar Str::toUtf8 e converter router para o router do laravel
 */
// public static function sanitizeString(string $texto): string
// {
//     if (env('INSTANCIA_DE_EXECUCAO') === 'CLI') {
//         trigger_error('Função sanitizeString não deve ser usada em CLI', E_USER_DEPRECATED);
//     }
//     setlocale(LC_CTYPE, 'pt_BR.UTF-8');
//     $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
//     return $texto;
// }
//}
