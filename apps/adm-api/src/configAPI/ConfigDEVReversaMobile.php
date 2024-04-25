<?php
namespace MobileStock\configAPI;

use MobileStock\api\CorreiosAPI\Bootstrap;
use MobileStock\api\CorreiosAPI\Config;
use MobileStock\api\CorreiosAPI\Model\AccessData;
use MobileStock\api\CorreiosAPI\Model\Diretoria;

/**
 * @author: Renan Zanelato <email:renan.zanelato96@gmail.com>
 * @co_author: https://github.com/send4store
 */
class ConfigDEVReversaMobile extends AccessData
{

    /**
     * Atalho para criar uma {@link AccessData} com os dados do ambiente de homologação.
     */
    public function __construct()
    {
        parent::__construct(
            array(
//                'usuario'           => '60618043',
//                'senha'             => '8o8otn',
//                'codAdministrativo' => '08082650',
//                'numeroContrato'    => '9912208555',
//                'cartaoPostagem'    => '0057018901',
//                'cnpjEmpresa'       => '34028316000103', // Obtido no método 'buscaCliente'.
//                'anoContrato'       => null, // Não consta no manual.
//                'diretoria'         => new Diretoria(Diretoria::DIRETORIA_DR_BRASILIA), // Obtido no método 'buscaCliente'.
                'usuario' => 'empresacws',
                'senha' => '123456',
                'codAdministrativo' => '17000190',
                'numeroContrato' => '9992157880',
                'cartaoPostagem' => '0067599079',
                'cnpjEmpresa' => '34028316000103', // Obtido no método 'buscaCliente'.
                'anoContrato' => null, // Não consta no manual.
                'diretoria' => new Diretoria(Diretoria::DIRETORIA_DR_BRASILIA), // Obtido no método 'buscaCliente'.
            )
        );
        try {
            Bootstrap::getConfig()->setEnv(Config::ENV_DEVELOPMENT);
        } catch (\Exception $e) {
            
        }
    }
}
