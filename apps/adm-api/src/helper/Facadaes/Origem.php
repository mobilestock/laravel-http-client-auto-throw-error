<?php

namespace MobileStock\helper\Facadaes;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool ehMl()
 * @method static bool ehMs()
 * @method static bool ehMed()
 * @method static bool ehAdm()
 * @method static bool ehAplicativoInterno()
 * @method static bool ehAplicativoEntregas()
 * @method static bool ehLp()
 */
class Origem extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \MobileStock\model\Origem::class;
    }
}
