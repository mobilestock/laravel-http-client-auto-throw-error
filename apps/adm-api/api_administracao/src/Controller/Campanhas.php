<?php

namespace api_administracao\Controller;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\repository\CampanhasRepository;
use MobileStock\repository\FotosRepository;

class Campanhas
{
    public function criarCampanha()
    {
        DB::beginTransaction();
        $dados = Request::all();
        Validador::validar($dados, ['url_pagina' => [Validador::OBRIGATORIO]]);
        Validador::validar($_FILES, ['file_imagem' => [Validador::OBRIGATORIO]]);
        $ultimaCampanha = CampanhasRepository::buscaUltimaCampanha();
        if ($ultimaCampanha) {
            CampanhasRepository::deletarCampanha($ultimaCampanha['id']);
        }
        $dateTimeAgora = new Carbon();
        $nomeArquivo = 'CAMPANHA_' . $dateTimeAgora->format('Y-m-d_H-i-s');
        $urlImagemAWS = FotosRepository::salvarFotoAwsS3($_FILES['file_imagem'], $nomeArquivo, 'PADRAO');
        CampanhasRepository::criarCampanha($dados['url_pagina'], $urlImagemAWS);
        DB::commit();
    }

    public function deletarCampanha(int $idCampanha)
    {
        DB::beginTransaction();
        CampanhasRepository::deletarCampanha($idCampanha);
        DB::commit();
    }
}
