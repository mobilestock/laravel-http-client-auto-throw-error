<?php

namespace MobileStock\model;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @deprecated 
 */
class Origem
{
    const MS = 'MS';
    const ML = 'ML';
    const MED = 'MED';
    const ADM = 'ADM';
    const LP = 'LP';
    const APLICATIVO_INTERNO = 'APLICATIVO_INTERNO';
    const APLICATIVO_ENTREGAS = 'APLICATIVO_ENTREGAS';

    protected string $valor;

    public function __construct(Request $request)
    {
        $referer = $request->header('referer') ?? '';
        switch ($referer) {
            case 'mobilereact':
                $this->valor = self::MS;
                return;
            case 'meulook':
                $this->valor = self::ML;
                return;
            case 'app-mobile-interno':
                $this->valor = self::APLICATIVO_INTERNO;
                return;
            case 'appEntregas':
                $this->valor = self::APLICATIVO_ENTREGAS;
                return;
        }

        switch ($this->tratarUrl($referer)) {
            case $this->tratarUrl($_ENV['URL_MOBILE']):
                $this->valor = self::ADM;
                return;
            case $this->tratarUrl($_ENV['URL_AREA_CLIENTE']):
                $this->valor = self::MS;
                return;
            case $this->tratarUrl($_ENV['URL_MEULOOK']):
                $this->valor = self::ML;
                return;
            case $this->tratarUrl($_ENV['URL_MED_API']):
                $this->valor = self::MED;
                return;
            case $this->tratarUrl($_ENV['URL_LOOKPAY']):
                $this->valor = self::LP;
                return;
            default:
                throw new Exception("Origem $referer não identificada");
        }
    }
    public function __call($name, $arguments): bool
    {
        $name = str_replace('eh', '', $name);
        $name = Str::ucsplit($name);
        $name = mb_strtoupper(implode('_', $name));
        return $this->verifica($name);
    }
    public function __toString(): string
    {
        return $this->valor ?? '';
    }
    public function verifica(...$parametros)
    {
        return in_array($this->valor, $parametros);
    }
    protected function tratarUrl(string $url): string
    {
        preg_match('/https?:\/\/[A-z0-9.]+:?[0-9]*/', $url, $matches);
        return $matches[0] ?? '';
    }
}
