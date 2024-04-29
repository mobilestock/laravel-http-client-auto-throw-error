<?php

namespace MobileStock\model;
use Illuminate\Support\Facades\DB;
use MobileStock\helper\CalculadorTransacao;

/**
 * https://github.com/mobilestock/web/issues/2903
 * @property int $id
 * @property int $numero_de_parcelas
 * @property float $mastercard
 * @property float $visa
 * @property float $elo
 * @property float $american_express
 * @property float $hiper
 * @property float $boleto
 * @property float $juros
 * @property float $pix
 * @property float $Juros_para_fornecedor
 * @property float $Juros_fixo_mobile
 */

class TaxasModel extends Model
{
    protected $table = 'taxas';
    protected $fillable = [
        'numero_de_parcelas',
        'mastercard',
        'visa',
        'elo',
        'american_express',
        'hiper',
        'boleto',
        'juros',
        'pix',
    ];

    public static function consultaValorBoleto(): float
    {
        $taxa = DB::selectOneColumn(
            'SELECT taxas.boleto
            FROM taxas
            LIMIT 1'
        );

        return $taxa;
    }

    public static function consultaValorTaxaParcela(int $parcela): float
    {
        $juros = DB::selectOneColumn(
            'SELECT taxas.juros
            FROM taxas
            WHERE taxas.numero_de_parcelas = ?',
            [$parcela]
        );

        return $juros;
    }
}
