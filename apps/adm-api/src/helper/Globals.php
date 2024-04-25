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
    /**
     * [
     * "sigla"= ''',
     * "nome"='']
     */
    const ESTADOS = [
        [
            'sigla' => 'RO',
            'nome' => 'Rondônia',
        ],
        [
            'sigla' => 'AC',
            'nome' => 'Acre',
        ],
        [
            'sigla' => 'AM',
            'nome' => 'Amazonas',
        ],
        [
            'sigla' => 'RR',
            'nome' => 'Roraima',
        ],
        [
            'sigla' => 'PA',
            'nome' => 'Pará',
        ],
        [
            'sigla' => 'AP',
            'nome' => 'Amapá',
        ],
        [
            'sigla' => 'TO',
            'nome' => 'Tocantins',
        ],
        [
            'sigla' => 'MA',
            'nome' => 'Maranhão',
        ],
        [
            'sigla' => 'PI',
            'nome' => 'Piauí',
        ],
        [
            'sigla' => 'CE',
            'nome' => 'Ceará',
        ],
        [
            'sigla' => 'RN',
            'nome' => 'Rio Grande do Norte',
        ],
        [
            'sigla' => 'PB',
            'nome' => 'Paraíba',
        ],
        [
            'sigla' => 'PE',
            'nome' => 'Pernambuco',
        ],
        [
            'sigla' => 'AL',
            'nome' => 'Alagoas',
        ],
        [
            'sigla' => 'SE',
            'nome' => 'Sergipe',
        ],
        [
            'sigla' => 'BA',
            'nome' => 'Bahia',
        ],
        [
            'sigla' => 'MG',
            'nome' => 'Minas Gerais',
        ],
        [
            'sigla' => 'ES',
            'nome' => 'Espírito Santo',
        ],
        [
            'sigla' => 'RJ',
            'nome' => 'Rio de Janeiro',
        ],
        [
            'sigla' => 'SP',
            'nome' => 'São Paulo',
        ],
        [
            'sigla' => 'PR',
            'nome' => 'Paraná',
        ],
        [
            'sigla' => 'SC',
            'nome' => 'Santa Catarina',
        ],
        [
            'sigla' => 'RS',
            'nome' => 'Rio Grande do Sul',
        ],
        [
            'sigla' => 'MS',
            'nome' => 'Mato Grosso do Sul',
        ],
        [
            'sigla' => 'MT',
            'nome' => 'Mato Grosso',
        ],
        [
            'sigla' => 'GO',
            'nome' => 'Goiás',
        ],
        [
            'sigla' => 'DF',
            'nome' => 'Distrito Federal',
        ],
    ];

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

    public static function geraQRCODE(string $valor): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=$valor";
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
