<?php

namespace api_cliente\Controller;

use MobileStock\repository\CampanhasRepository;

class Campanhas
{
    public function buscarUltimaCampanha()
    {
        $campanha = CampanhasRepository::buscaUltimaCampanha();
        return $campanha;
    }
}
