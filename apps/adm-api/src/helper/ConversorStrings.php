<?php

namespace MobileStock\helper;

/**
 * Debito técnico: https://github.com/mobilestock/backend/issues/221
 * Essa classe sera movida para a classe ConversorString e com isso teremos que rever todos os métodos,
 * a tarefa #2999 nao poderá fazer a mudança de uma vez devido a urgência de segunda feira os funcionários da empresa
 * ja imprimirem as etiquetas no novo formato.
 * @author: Gustavo210
 *
 * @deprecated
 */
class ConversorStrings
{
    private $strings;

    public function __construct(array $strings)
    {
        $this->strings = $strings;
    }

    public function convertePrimeiraLetraMaiusculo(): array
    {
        return array_map(function ($possivelString) {
            if (is_string($possivelString) && !is_numeric($possivelString)) {
                return ucfirst($possivelString);
            }

            return $possivelString;
        }, $this->strings);
    }

    public function converteSnakeCaseParaCamelCase($prefixo = ''): array
    {
        return array_map(function ($possivelString) use ($prefixo) {
            if (is_string($possivelString) && !is_numeric($possivelString)) {
                $str = $prefixo;
                $array = explode('_', $possivelString);
                for ($i = 0; $i < sizeof($array); $i++) {
                    $str .= ucfirst($array[$i]);
                }
                $possivelString = $str;
            }

            return $possivelString;
        }, $this->strings);
    }

    public static function removeAcentos(string $pesquisa): string
    {
        $pesquisa = strtr($pesquisa, [
            'Ĳ' => 'I',
            'Ö' => 'O',
            'Œ' => 'O',
            'Ü' => 'U',
            'ä' => 'a',
            'æ' => 'a',
            'ĳ' => 'i',
            'ö' => 'o',
            'œ' => 'o',
            'ü' => 'u',
            'ß' => 's',
            'ſ' => 's',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'A',
            'Ā' => 'A',
            'Ą' => 'A',
            'Ă' => 'A',
            'Ç' => 'C',
            'Ć' => 'C',
            'Č' => 'C',
            'Ĉ' => 'C',
            'Ċ' => 'C',
            'Ď' => 'D',
            'Đ' => 'D',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ē' => 'E',
            'Ę' => 'E',
            'Ě' => 'E',
            'Ĕ' => 'E',
            'Ė' => 'E',
            'Ĝ' => 'G',
            'Ğ' => 'G',
            'Ġ' => 'G',
            'Ģ' => 'G',
            'Ĥ' => 'H',
            'Ħ' => 'H',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ī' => 'I',
            'Ĩ' => 'I',
            'Ĭ' => 'I',
            'Į' => 'I',
            'İ' => 'I',
            'Ĵ' => 'J',
            'Ķ' => 'K',
            'Ľ' => 'K',
            'Ĺ' => 'K',
            'Ļ' => 'K',
            'Ŀ' => 'K',
            'Ł' => 'L',
            'Ñ' => 'N',
            'Ń' => 'N',
            'Ň' => 'N',
            'Ņ' => 'N',
            'Ŋ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ø' => 'O',
            'Ō' => 'O',
            'Ő' => 'O',
            'Ŏ' => 'O',
            'Ŕ' => 'R',
            'Ř' => 'R',
            'Ŗ' => 'R',
            'Ś' => 'S',
            'Ş' => 'S',
            'Ŝ' => 'S',
            'Ș' => 'S',
            'Š' => 'S',
            'Ť' => 'T',
            'Ţ' => 'T',
            'Ŧ' => 'T',
            'Ț' => 'T',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ū' => 'U',
            'Ů' => 'U',
            'Ű' => 'U',
            'Ŭ' => 'U',
            'Ũ' => 'U',
            'Ų' => 'U',
            'Ŵ' => 'W',
            'Ŷ' => 'Y',
            'Ÿ' => 'Y',
            'Ý' => 'Y',
            'Ź' => 'Z',
            'Ż' => 'Z',
            'Ž' => 'Z',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ā' => 'a',
            'ą' => 'a',
            'ă' => 'a',
            'å' => 'a',
            'ç' => 'c',
            'ć' => 'c',
            'č' => 'c',
            'ĉ' => 'c',
            'ċ' => 'c',
            'ď' => 'd',
            'đ' => 'd',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ē' => 'e',
            'ę' => 'e',
            'ě' => 'e',
            'ĕ' => 'e',
            'ė' => 'e',
            'ƒ' => 'f',
            'ĝ' => 'g',
            'ğ' => 'g',
            'ġ' => 'g',
            'ģ' => 'g',
            'ĥ' => 'h',
            'ħ' => 'h',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ī' => 'i',
            'ĩ' => 'i',
            'ĭ' => 'i',
            'į' => 'i',
            'ı' => 'i',
            'ĵ' => 'j',
            'ķ' => 'k',
            'ĸ' => 'k',
            'ł' => 'l',
            'ľ' => 'l',
            'ĺ' => 'l',
            'ļ' => 'l',
            'ŀ' => 'l',
            'ñ' => 'n',
            'ń' => 'n',
            'ň' => 'n',
            'ņ' => 'n',
            'ŉ' => 'n',
            'ŋ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ø' => 'o',
            'ō' => 'o',
            'ő' => 'o',
            'ŏ' => 'o',
            'ŕ' => 'r',
            'ř' => 'r',
            'ŗ' => 'r',
            'ś' => 's',
            'š' => 's',
            'ť' => 't',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ū' => 'u',
            'ů' => 'u',
            'ű' => 'u',
            'ŭ' => 'u',
            'ũ' => 'u',
            'ų' => 'u',
            'ŵ' => 'w',
            'ÿ' => 'y',
            'ý' => 'y',
            'ŷ' => 'y',
            'ż' => 'z',
            'ź' => 'z',
            'ž' => 'z',
            'Α' => 'A',
            'Ά' => 'A',
            'Ἀ' => 'A',
            'Ἁ' => 'A',
            'Ἂ' => 'A',
            'Ἃ' => 'A',
            'Ἄ' => 'A',
            'Ἅ' => 'A',
            'Ἆ' => 'A',
            'Ἇ' => 'A',
            'ᾈ' => 'A',
            'ᾉ' => 'A',
            'ᾊ' => 'A',
            'ᾋ' => 'A',
            'ᾌ' => 'A',
            'ᾍ' => 'A',
            'ᾎ' => 'A',
            'ᾏ' => 'A',
            'Ᾰ' => 'A',
            'Ᾱ' => 'A',
            'Ὰ' => 'A',
            'ᾼ' => 'A',
            'Β' => 'B',
            'Γ' => 'G',
            'Δ' => 'D',
            'Ε' => 'E',
            'Έ' => 'E',
            'Ἐ' => 'E',
            'Ἑ' => 'E',
            'Ἒ' => 'E',
            'Ἓ' => 'E',
            'Ἔ' => 'E',
            'Ἕ' => 'E',
            'Ὲ' => 'E',
            'Ζ' => 'Z',
            'Η' => 'I',
            'Ή' => 'I',
            'Ἠ' => 'I',
            'Ἡ' => 'I',
            'Ἢ' => 'I',
            'Ἣ' => 'I',
            'Ἤ' => 'I',
            'Ἥ' => 'I',
            'Ἦ' => 'I',
            'Ἧ' => 'I',
            'ᾘ' => 'I',
            'ᾙ' => 'I',
            'ᾚ' => 'I',
            'ᾛ' => 'I',
            'ᾜ' => 'I',
            'ᾝ' => 'I',
            'ᾞ' => 'I',
            'ᾟ' => 'I',
            'Ὴ' => 'I',
            'ῌ' => 'I',
            'Θ' => 'T',
            'Ι' => 'I',
            'Ί' => 'I',
            'Ϊ' => 'I',
            'Ἰ' => 'I',
            'Ἱ' => 'I',
            'Ἲ' => 'I',
            'Ἳ' => 'I',
            'Ἴ' => 'I',
            'Ἵ' => 'I',
            'Ἶ' => 'I',
            'Ἷ' => 'I',
            'Ῐ' => 'I',
            'Ῑ' => 'I',
            'Ὶ' => 'I',
            'Κ' => 'K',
            'Λ' => 'L',
            'Μ' => 'M',
            'Ν' => 'N',
            'Ξ' => 'K',
            'Ο' => 'O',
            'Ό' => 'O',
            'Ὀ' => 'O',
            'Ὁ' => 'O',
            'Ὂ' => 'O',
            'Ὃ' => 'O',
            'Ὄ' => 'O',
            'Ὅ' => 'O',
            'Ὸ' => 'O',
            'Π' => 'P',
            'Ρ' => 'R',
            'Ῥ' => 'R',
            'Σ' => 'S',
            'Τ' => 'T',
            'Υ' => 'Y',
            'Ύ' => 'Y',
            'Ϋ' => 'Y',
            'Ὑ' => 'Y',
            'Ὓ' => 'Y',
            'Ὕ' => 'Y',
            'Ὗ' => 'Y',
            'Ῠ' => 'Y',
            'Ῡ' => 'Y',
            'Ὺ' => 'Y',
            'Φ' => 'F',
            'Χ' => 'X',
            'Ψ' => 'P',
            'Ω' => 'O',
            'Ώ' => 'O',
            'Ὠ' => 'O',
            'Ὡ' => 'O',
            'Ὢ' => 'O',
            'Ὣ' => 'O',
            'Ὤ' => 'O',
            'Ὥ' => 'O',
            'Ὦ' => 'O',
            'Ὧ' => 'O',
            'ᾨ' => 'O',
            'ᾩ' => 'O',
            'ᾪ' => 'O',
            'ᾫ' => 'O',
            'ᾬ' => 'O',
            'ᾭ' => 'O',
            'ᾮ' => 'O',
            'ᾯ' => 'O',
            'Ὼ' => 'O',
            'ῼ' => 'O',
            'α' => 'a',
            'ά' => 'a',
            'ἀ' => 'a',
            'ἁ' => 'a',
            'ἂ' => 'a',
            'ἃ' => 'a',
            'ἄ' => 'a',
            'ἅ' => 'a',
            'ἆ' => 'a',
            'ἇ' => 'a',
            'ᾀ' => 'a',
            'ᾁ' => 'a',
            'ᾂ' => 'a',
            'ᾃ' => 'a',
            'ᾄ' => 'a',
            'ᾅ' => 'a',
            'ᾆ' => 'a',
            'ᾇ' => 'a',
            'ὰ' => 'a',
            'ᾰ' => 'a',
            'ᾱ' => 'a',
            'ᾲ' => 'a',
            'ᾳ' => 'a',
            'ᾴ' => 'a',
            'ᾶ' => 'a',
            'ᾷ' => 'a',
            'β' => 'b',
            'γ' => 'g',
            'δ' => 'd',
            'ε' => 'e',
            'έ' => 'e',
            'ἐ' => 'e',
            'ἑ' => 'e',
            'ἒ' => 'e',
            'ἓ' => 'e',
            'ἔ' => 'e',
            'ἕ' => 'e',
            'ὲ' => 'e',
            'ζ' => 'z',
            'η' => 'i',
            'ή' => 'i',
            'ἠ' => 'i',
            'ἡ' => 'i',
            'ἢ' => 'i',
            'ἣ' => 'i',
            'ἤ' => 'i',
            'ἥ' => 'i',
            'ἦ' => 'i',
            'ἧ' => 'i',
            'ᾐ' => 'i',
            'ᾑ' => 'i',
            'ᾒ' => 'i',
            'ᾓ' => 'i',
            'ᾔ' => 'i',
            'ᾕ' => 'i',
            'ᾖ' => 'i',
            'ᾗ' => 'i',
            'ὴ' => 'i',
            'ῂ' => 'i',
            'ῃ' => 'i',
            'ῄ' => 'i',
            'ῆ' => 'i',
            'ῇ' => 'i',
            'θ' => 't',
            'ι' => 'i',
            'ί' => 'i',
            'ϊ' => 'i',
            'ΐ' => 'i',
            'ἰ' => 'i',
            'ἱ' => 'i',
            'ἲ' => 'i',
            'ἳ' => 'i',
            'ἴ' => 'i',
            'ἵ' => 'i',
            'ἶ' => 'i',
            'ἷ' => 'i',
            'ὶ' => 'i',
            'ῐ' => 'i',
            'ῑ' => 'i',
            'ῒ' => 'i',
            'ῖ' => 'i',
            'ῗ' => 'i',
            'κ' => 'k',
            'λ' => 'l',
            'μ' => 'm',
            'ν' => 'n',
            'ξ' => 'k',
            'ο' => 'o',
            'ό' => 'o',
            'ὀ' => 'o',
            'ὁ' => 'o',
            'ὂ' => 'o',
            'ὃ' => 'o',
            'ὄ' => 'o',
            'ὅ' => 'o',
            'ὸ' => 'o',
            'π' => 'p',
            'ρ' => 'r',
            'ῤ' => 'r',
            'ῥ' => 'r',
            'σ' => 's',
            'ς' => 's',
            'τ' => 't',
            'υ' => 'y',
            'ύ' => 'y',
            'ϋ' => 'y',
            'ΰ' => 'y',
            'ὐ' => 'y',
            'ὑ' => 'y',
            'ὒ' => 'y',
            'ὓ' => 'y',
            'ὔ' => 'y',
            'ὕ' => 'y',
            'ὖ' => 'y',
            'ὗ' => 'y',
            'ὺ' => 'y',
            'ῠ' => 'y',
            'ῡ' => 'y',
            'ῢ' => 'y',
            'ῦ' => 'y',
            'ῧ' => 'y',
            'φ' => 'f',
            'χ' => 'x',
            'ψ' => 'p',
            'ω' => 'o',
            'ώ' => 'o',
            'ὠ' => 'o',
            'ὡ' => 'o',
            'ὢ' => 'o',
            'ὣ' => 'o',
            'ὤ' => 'o',
            'ὥ' => 'o',
            'ὦ' => 'o',
            'ὧ' => 'o',
            'ᾠ' => 'o',
            'ᾡ' => 'o',
            'ᾢ' => 'o',
            'ᾣ' => 'o',
            'ᾤ' => 'o',
            'ᾥ' => 'o',
            'ᾦ' => 'o',
            'ᾧ' => 'o',
            'ὼ' => 'o',
            'ῲ' => 'o',
            'ῳ' => 'o',
            'ῴ' => 'o',
            'ῶ' => 'o',
            'ῷ' => 'o',
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'E',
            'Ж' => 'Z',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'I',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'K',
            'Ц' => 'T',
            'Ч' => 'C',
            'Ш' => 'S',
            'Щ' => 'S',
            'Ы' => 'Y',
            'Э' => 'E',
            'Ю' => 'Y',
            'Я' => 'Y',
            'а' => 'A',
            'б' => 'B',
            'в' => 'V',
            'г' => 'G',
            'д' => 'D',
            'е' => 'E',
            'ё' => 'E',
            'ж' => 'Z',
            'з' => 'Z',
            'и' => 'I',
            'й' => 'I',
            'к' => 'K',
            'л' => 'L',
            'м' => 'M',
            'н' => 'N',
            'о' => 'O',
            'п' => 'P',
            'р' => 'R',
            'с' => 'S',
            'т' => 'T',
            'у' => 'U',
            'ф' => 'F',
            'х' => 'K',
            'ц' => 'T',
            'ч' => 'C',
            'ш' => 'S',
            'щ' => 'S',
            'ы' => 'Y',
            'э' => 'E',
            'ю' => 'Y',
            'я' => 'Y',
            'ð' => 'd',
            'Ð' => 'D',
            'þ' => 't',
            'Þ' => 'T',
            'ა' => 'a',
            'ბ' => 'b',
            'გ' => 'g',
            'დ' => 'd',
            'ე' => 'e',
            'ვ' => 'v',
            'ზ' => 'z',
            'თ' => 't',
            'ი' => 'i',
            'კ' => 'k',
            'ლ' => 'l',
            'მ' => 'm',
            'ნ' => 'n',
            'ო' => 'o',
            'პ' => 'p',
            'ჟ' => 'z',
            'რ' => 'r',
            'ს' => 's',
            'ტ' => 't',
            'უ' => 'u',
            'ფ' => 'p',
            'ქ' => 'k',
            'ღ' => 'g',
            'ყ' => 'q',
            'შ' => 's',
            'ჩ' => 'c',
            'ც' => 't',
            'ძ' => 'd',
            'წ' => 't',
            'ჭ' => 'c',
            'ხ' => 'k',
            'ჯ' => 'j',
            'ჰ' => 'h',
        ]);
        return preg_replace('/[^A-Za-z0-9\- ]/', '', $pesquisa);
    }
    public static function capitalize(
        string $titulo,
        ?array $delimitadores = [' ', '-', '.', "'", "O'", 'Mc'],
        ?array $excessao = ['de', 'da', 'dos', 'das', 'do', 'I', 'II', 'III', 'IV', 'V', 'VI']
    ): string {
        $titulo = mb_convert_case($titulo, MB_CASE_TITLE, 'UTF-8');
        foreach ($delimitadores as $delimitador) {
            $palavra = explode($delimitador, $titulo);
            $listaDePalavras = [];
            foreach ($palavra as $caractere) {
                switch (true) {
                    case in_array(mb_strtoupper($caractere, 'UTF-8'), $excessao):
                        $caractere = mb_strtoupper($caractere, 'UTF-8');
                        break;

                    case in_array(mb_strtolower($caractere, 'UTF-8'), $excessao):
                        $caractere = mb_strtolower($caractere, 'UTF-8');
                        break;
                    case !in_array($caractere, $excessao):
                        $caractere = ucfirst($caractere);
                        break;
                }
                $listaDePalavras[] = $caractere;
            }
            $titulo = join($delimitador, $listaDePalavras);
        }
        return $titulo;
    }

    public static function sanitizeString(?string $str): ?string
    {
        if (!$str) {
            return null;
        }
        $str = preg_replace('/[áàãâä]/ui', 'a', $str);
        $str = preg_replace('/[éèêë]/ui', 'e', $str);
        $str = preg_replace('/[íìîï]/ui', 'i', $str);
        $str = preg_replace('/[óòõôö]/ui', 'o', $str);
        $str = preg_replace('/[úùûü]/ui', 'u', $str);
        $str = preg_replace('/[ç]/ui', 'c', $str);
        $str = preg_replace('/[^a-zA-Z0-9\,\-\s]/i', '_', $str);
        return $str;
    }

    public static function formataTelefone($phone)
    {
        $formatedPhone = preg_replace('/[^0-9]/', '', $phone);
        $matches = [];
        preg_match('/^([0-9]{2})([0-9]{4,5})([0-9]{4})$/', $formatedPhone, $matches);
        if ($matches) {
            return '(' . $matches[1] . ') ' . $matches[2] . '-' . $matches[3];
        }

        return $phone;
    }
    /**
     * O PDOCallTrait já faz esse tratamento
     * @deprecated
     */
    public static function trataRetornoBanco($pdoMessage): string
    {
        if (mb_stripos($pdoMessage, 'Integrity constraint violation') !== false) {
            return 'Registro já existe';
        }

        if (mb_stripos($pdoMessage, 'Data too long for column') !== false) {
            return 'Campo "' .
                mb_substr(
                    $pdoMessage,
                    mb_stripos($pdoMessage, "'") + 1,
                    mb_stripos(mb_substr($pdoMessage, mb_stripos($pdoMessage, "'") + 1), "'")
                ) .
                '" inválido';
        }

        if (mb_stripos($pdoMessage, 'SQLSTATE[45000]') !== false) {
            return mb_substr($pdoMessage, mb_stripos($pdoMessage, '>>:') + 4);
        }

        return $pdoMessage;
    }

    public static function maiorEMenorValorEmString(array $valores)
    {
        return implode(
            ',',
            array_reduce(
                $valores,
                function (array $total, array $item) {
                    $primeiroValor = $total[0] ?? PHP_INT_MAX;
                    $segundoValor = $total[1] ?? 0;

                    if ($item['preco'] < $primeiroValor) {
                        if (isset($total[0]) && $total[0] !== PHP_INT_MAX) {
                            $total[1] = $total[0];
                        }
                        $total[0] = $item['preco'];
                    } elseif ($item['preco'] > $segundoValor) {
                        $total[1] = $item['preco'];
                    }
                    return $total;
                },
                []
            )
        );
    }

    public static function tratarTermoOpensearch(string $pesquisa): string
    {
        // Deixa tudo em minúsculo
        $pesquisa = mb_strtolower(trim($pesquisa));
        // Remove acentos
        $pesquisa = ConversorStrings::sanitizeString($pesquisa);
        // Substitui grupos de caracteres especiais por um único underline
        $pesquisa = preg_replace('/([^A-z0-9]+|_+)/', '_', $pesquisa);
        // Substitui grupos de underline por um único espaço
        $pesquisa = preg_replace('/(_+)/', ' ', $pesquisa);
        // Remove espaços no início e no fim da string
        $pesquisa = trim($pesquisa);

        return $pesquisa;
    }
}
