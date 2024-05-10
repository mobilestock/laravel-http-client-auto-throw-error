<?php

namespace MobileStock\helper;

use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use MobileStock\database\Conexao;
use MobileStock\service\Pagamento\PagamentoCartaoCielo;
use MobileStock\service\Pagamento\PagamentoCartaoIugu;
use MobileStock\service\Pagamento\PagamentoCartaoZoop;
use MobileStock\service\Pagamento\PagamentoCreditoInterno;
use MobileStock\service\Pagamento\PagamentoPixBoletoIugu;
use MobileStock\service\Pagamento\PagamentoPixSicoob;

/**
 * Facade helper de funções globais
 * @package MobileStock\helper
 */
abstract class Globals
{
    const NUM_ENTREGADOR = '553799732315';
    const NUM_FABIO = '37991038073';
    const MODERATE_CONTENT_TOKEN = '364f8c336e3c3da50ed8f98bb8d6048d';
    const MARKETPLACE_SELLERID_MOBILESTOCK = '54dd198e0380491b91dacb67e0f88d7c';
    const JWT_KEY = 'cfe9c06f9b4f181edbf4e529a556b9b537bc84c667e8072136bdab085bd18';

    const INTERFACES_PAGAMENTO = [
        PagamentoCartaoCielo::class,
        PagamentoCartaoIugu::class,
        PagamentoPixBoletoIugu::class,
        PagamentoCreditoInterno::class,
        PagamentoCartaoZoop::class,
        PagamentoPixSicoob::class,
    ];

    const BOOTSTRAPPERS = [
        HandleExceptions::class,
        RegisterFacades::class,
        BootProviders::class,
        RegisterProviders::class,
    ];

    const PAINEIS_DE_IMPRESSAO = [
        3750,
        3751,
        3752,
        1000,
        1100,
        1101,
        1001,
        1102,
        1103,
        1002,
        1104,
        1003,
        1105,
        1004,
        1106,
        1005,
        1107,
        1006,
        1108,
        1007,
        1109,
        1008,
        1110,
        1009,
        1111,
        1010,
        1011,
        1012,
        1200,
        1013,
        1201,
        1014,
        1202,
        1015,
        1203,
        1016,
        1204,
        1017,
        1205,
        1018,
        1206,
        1019,
        1207,
        1020,
        1208,
        1021,
        1209,
        1022,
        1210,
        1023,
        1211,
        1024,
        1025,
        1212,
        1213,
        1026,
        1027,
        1214,
        1028,
        1215,
        1216,
        1029,
        1030,
        1031,
        1032,
        1632,
        1631,
        1415,
        1630,
        1629,
        1414,
        1628,
        1627,
        1413,
        1626,
        1625,
        1412,
        1624,
        1623,
        1411,
        1622,
        1621,
        1410,
        1620,
        1619,
        1409,
        1618,
        1617,
        1408,
        1616,
        1615,
        1407,
        1614,
        1613,
        1406,
        1612,
        1611,
        1405,
        1610,
        1609,
        1404,
        1608,
        1607,
        1403,
        1606,
        1402,
        1605,
        1604,
        1603,
        1401,
        1602,
        1400,
        1601,
        1600,
        1311,
        1513,
        1310,
        1512,
        1511,
        1309,
        1510,
        1308,
        1509,
        1307,
        1508,
        1507,
        1306,
        1506,
        1305,
        1505,
        1304,
        1504,
        1503,
        1303,
        1502,
        1302,
        1501,
        1301,
        1500,
        1300,
        1900,
        1700,
        1701,
        1901,
        1702,
        1703,
        1902,
        1704,
        1705,
        1903,
        1706,
        1707,
        1904,
        1708,
        1709,
        1905,
        1710,
        1711,
        1906,
        1712,
        1713,
        1907,
        1714,
        1800,
        2000,
        1801,
        1802,
        2001,
        1803,
        1804,
        1805,
        2002,
        1806,
        1807,
        2003,
        1808,
        1809,
        1810,
        2004,
        1811,
        1812,
        2005,
        1813,
        1814,
        1815,
        2006,
        1816,
        1817,
        1818,
        2007,
        1819,
        1820,
        2008,
        1821,
        1822,
        1823,
        2009,
        1824,
        1825,
        1826,
        2010,
        1827,
        1828,
        2011,
        1829,
        1830,
        1831,
        1832,
        2012,
        2013,
        1033,
        1034,
        1035,
        1036,
        2414,
        2413,
        2213,
        2412,
        2212,
        2411,
        2211,
        2410,
        2210,
        2409,
        2209,
        2408,
        2208,
        2407,
        2207,
        2406,
        2206,
        2405,
        2205,
        2404,
        2204,
        2403,
        2203,
        2402,
        2202,
        2401,
        2201,
        2400,
        2200,
        2107,
        2307,
        2106,
        2306,
        2105,
        2305,
        2104,
        2304,
        2103,
        2303,
        2102,
        2302,
        2301,
        2101,
        2100,
        2300,
        2500,
        2700,
        2501,
        2701,
        2702,
        2502,
        2503,
        2703,
        2504,
        2704,
        2505,
        2705,
        2506,
        2706,
        2507,
        2707,
        2600,
        2601,
        2602,
        2603,
        2604,
        2605,
        2606,
        2607,
        2608,
        2609,
        2610,
        2611,
        2612,
        2613,
        2614,
        1037,
        1038,
        1039,
        1040,
        1041,
        1042,
        1043,
        1044,
        1045,
        1046,
        1047,
        1048,
        1049,
        1050,
        1051,
        1052,
        1053,
        1054,
        1055,
        1056,
        1057,
        1058,
        1059,
        1060,
        1061,
        1062,
        1063,
        1064,
        1065,
        2907,
        2906,
        2905,
        2904,
        2903,
        2902,
        2901,
        2900,
        2800,
        2801,
        2802,
        2803,
        2804,
        2805,
        2806,
        2807,
        2808,
        2809,
        2810,
        2811,
        2812,
        2813,
        2814,
        2815,
        2816,
        2817,
        2818,
        2819,
        2820,
        2821,
        2822,
        2823,
        2824,
        2825,
        2826,
        2827,
        2828,
        2829,
        2830,
        2831,
        2832,
        2833,
        2834,
        2835,
        2836,
        2837,
        2838,
        2839,
        2840,
        2841,
        2842,
        2843,
        2844,
        2845,
        2846,
        2847,
        2848,
        2849,
        2850,
        2851,
        2852,
        2853,
        2854,
        2855,
        2856,
        2857,
        2858,
        2859,
        2860,
        2861,
        2862,
        2863,
        2864,
        2865,
        2866,
        2867,
        2868,
        2869,
        2870,
        2871,
        2872,
        2873,
        2874,
        2875,
        2876,
        2878,
        2879,
        2880,
        3000,
        3001,
        3002,
        3003,
        3004,
        3005,
        3006,
        3007,
        3008,
        3009,
        3010,
        3011,
        3012,
        3013,
        3014,
        3015,
        3016,
        3017,
        3018,
        3019,
        3020,
        3021,
        3022,
        3023,
        3024,
        3025,
        3026,
        3027,
        3028,
        3029,
        3030,
        3031,
        3032,
        3033,
        3034,
        3035,
        3036,
        3037,
        3038,
        3039,
        3040,
        3041,
        3042,
        3043,
        3044,
        3045,
        3046,
        3047,
        3048,
        3049,
        3050,
        3051,
        3052,
        3053,
        3054,
        3055,
        3056,
        3057,
        3058,
        3059,
        3060,
        3161,
        3162,
        3163,
        3164,
        3165,
        3166,
        3167,
        3168,
        3169,
        3170,
        3150,
        3149,
        3148,
        3147,
        3146,
        3145,
        3144,
        3143,
        3142,
        3141,
        3140,
        3139,
        3138,
        3137,
        3136,
        3135,
        3134,
        3133,
        3132,
        3131,
        3130,
        3129,
        3128,
        3127,
        3126,
        3125,
        3124,
        3123,
        3122,
        3121,
        3120,
        3119,
        3118,
        3117,
        3116,
        3115,
        3114,
        3113,
        3112,
        3111,
        3110,
        3109,
        3108,
        3107,
        3106,
        3105,
        3104,
        3103,
        3102,
        3101,
        3100,
    ];

    //    private static ?EventDispatcher $eventDispatcher;

    public static function S3_OPTIONS($opcao = 'PADRAO')
    {
        return $_ENV['S3_OPTIONS_ARRAY'][$opcao];
    }

    public static function getYears(int $quantYears)
    {
        $ano = date('Y') + $quantYears;
        $anos = [];
        for ($i = 2019; $i < $ano + 1; $i++) {
            $anos[] = $i;
        }
        return $anos;
    }

    public static function geraToken(): void
    {
        $_SESSION['token'] = $_SESSION['id_cliente']
            ? RegrasAutenticacao::geraTokenPadrao(Conexao::criarConexao(), $_SESSION['id_cliente'])
            : '';
    }

    /**
     * @deprecated
     * A idéia é usar uma função que busque o ENV diretamente
     */
    public static function geraQRCODE(string $valor): string
    {
        return "{$_ENV['URL_GERADOR_QRCODE']}$valor";
    }

    /**
     * Usar do helper/ConversorStrings.php
     * @deprecated
     * @author Sávio
     */
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
     * Gera uma string de referência em Base64 a partir de um ID colaborador.
     * A string gerada não tem igualdades pra ser segura ao passar por URL.
     *
     * @param int $idUsuario ID de colaborador do usuário
     *
     * @return string base64 do ID colaborador fornecido.
     */
    public static function createRefID(int $idUsuario): string
    {
        return strtr(base64_encode($idUsuario), '+/=', '.-_');
    }

    /**
     * Válida uma string de referência em Base64 a partir de uma string
     *
     * @param string $refID id colaborador base64 encoded.
     *
     * @throws InvalidArgumentException Se o refID não tiver nenhum ID colaborador.
     * @return int id colaborador do base64 fornecido.
     */
    public static function parseRefID(string $refID): int
    {
        $decodedID = (int) base64_decode(strtr($refID, '.-_', '+/='));
        if ($decodedID !== 0) {
            return $decodedID;
        } else {
            throw new InvalidArgumentException('refID inválido!');
        }
    }
    /**
     * Retorna a distância em KM entre duas coordenadas geográficas.
     *
     * @param int $latitudeFrom Latitude do primeiro ponto.
     * @param int $longitudeFrom Longitude do primeiro ponto.
     * @param int $latitudeTo Latitude do segundo ponto.
     * @param int $longitudeTo Longitude do segundo ponto.
     *
     * @return string distância em KM entre os dois pontos.
     */
    public static function Haversine($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
    {
        $rad = M_PI / 180;
        $theta = $longitudeFrom - $longitudeTo;
        $dist =
            sin($latitudeFrom * $rad) * sin($latitudeTo * $rad) +
            cos($latitudeFrom * $rad) * cos($latitudeTo * $rad) * cos($theta * $rad);

        $result = (acos($dist) / $rad) * 60 * 1.853;
        return is_nan($result) ? 0 : $result;
    }

    //    public static function events(): EventDispatcher
    //    {
    //        return self::$eventDispatcher ??= new EventDispatcher();
    //    }
}
