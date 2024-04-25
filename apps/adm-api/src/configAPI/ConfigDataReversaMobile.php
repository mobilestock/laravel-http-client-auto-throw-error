<?php
namespace MobileStock\configAPI;

use MobileStock\api\CorreiosAPI\Bootstrap;
use MobileStock\api\CorreiosAPI\Config;
use MobileStock\api\CorreiosAPI\Model\AccessData;
use MobileStock\api\CorreiosAPI\Model\Diretoria;

// use PhpSigep\Model\AccessData;
// use PhpSigep\Model\Diretoria;

/**
 * @author: Renan Zanelato <email:renan.zanelato96@gmail.com>
 * @co_author: https://github.com/send4store
 */
class ConfigDataReversaMobile extends AccessData
{

    /**
     * Atalho para criar uma {@link AccessData} com os dados do ambiente de homologação.
     */
    public function __construct()
    {
        parent::__construct(
            array(
                    'usuario' => '25252339000106',
                    //'senha' => '5sd65x',
                    'senha' => 'art309123082601',
                    'codAdministrativo' => '16032543',
                    'numeroContrato' => '9912391242',
                    'cartaoPostagem' => '0071946241',
                    'cnpjEmpresa' => '25252339000106', // Obtido no método 'buscaCliente'.
                    'anoContrato' => null, // Não consta no manual.
                    'diretoria' => new Diretoria(Diretoria::DIRETORIA_DR_BRASILIA), // Obtido no método 'buscaCliente'.
            )
        );
        try {
            Bootstrap::getConfig()->setEnv(Config::ENV_PRODUCTION);
        } catch (\Exception $e) {
            
        }
    }
}
