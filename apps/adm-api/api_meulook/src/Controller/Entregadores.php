<?php

namespace api_meulook\Controller;
use api_meulook\Models\Request_m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorDocumento;
use MobileStock\model\TransportadoresRaio;
use MobileStock\repository\FotosRepository;
use MobileStock\service\ColaboradoresService;
use MobileStock\service\TipoFreteService;
use MobileStock\service\TransporteService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class Entregadores extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = '1';
        parent::__construct();
    }

    public function solicitarCadastro(TipoFreteService $tipoFreteService, TransporteService $transporteService)
    {
        DB::beginTransaction();
        $dadosJson = Request::all();

        Validador::validar($_FILES, [
            'foto_documento_habilitacao' => [Validador::OBRIGATORIO],
            'foto_documento_veiculo' => [Validador::OBRIGATORIO],
        ]);
        Validador::validar($dadosJson, [
            'id_colaborador_ponto_coleta' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'cidades' => [Validador::OBRIGATORIO, Validador::JSON],
        ]);
        $dadosJson['cidades'] = json_decode($dadosJson['cidades'], true);
        Validador::validar($dadosJson, [
            'cidades' => [Validador::OBRIGATORIO, Validador::ARRAY, Validador::TAMANHO_MINIMO(1)],
        ]);

        $idUsuario = Auth::user()->id;
        $idColaborador = Auth::user()->id_colaborador;
        $situacao = TipoFreteService::categoriaTipoFrete();
        if (!empty($situacao)) {
            throw new ConflictHttpException('Cadastro já solicitado!');
        }

        $colaborador = ColaboradoresService::buscaCadastroColaborador($idColaborador);
        $tipoFreteService->id_colaborador = $idColaborador;
        $tipoFreteService->id_usuario = $idUsuario;
        $tipoFreteService->nome = $colaborador['razao_social'];
        $tipoFreteService->foto = $colaborador['foto_perfil'];
        $tipoFreteService->tipo_ponto = 'PM';
        $tipoFreteService->categoria = 'PE';
        $tipoFreteService->percentual_comissao = 0; // 0 pois a comissão não é fixa.
        $tipoFreteService->latitude = 0;
        $tipoFreteService->longitude = 0;
        $tipoFreteService->salva(DB::getPdo());

        $transporteService->id_colaborador = $idColaborador;
        $transporteService->tipo_transporte = 'ENTREGADOR';
        $transporteService->situacao = 'PR';
        $transporteService->salva(DB::getPdo());

        foreach ($dadosJson['cidades'] as $cidade) {
            Validador::validar($cidade, [
                'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'raios' => [Validador::ARRAY],
            ]);

            foreach ($cidade['raios'] as $raio) {
                Validador::validar($raio, [
                    'latitude' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'longitude' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'area' => [Validador::OBRIGATORIO, Validador::NUMERO],
                    'apelido' => [
                        Validador::SE(Validador::OBRIGATORIO, [Validador::SANIZAR, Validador::TAMANHO_MAXIMO(50)]),
                    ],
                ]);

                $novoRaio = [
                    'id_colaborador' => $idColaborador,
                    'id_cidade' => $cidade['id'],
                    'latitude' => $raio['latitude'],
                    'longitude' => $raio['longitude'],
                    'raio' => $raio['area'],
                ];
                if ($raio['apelido']) {
                    $novoRaio['apelido'] = $raio['apelido'];
                }

                TransportadoresRaio::create($novoRaio);
            }
        }
        TipoFreteService::adicionaCentralColeta(
            DB::getPdo(),
            $idColaborador,
            $dadosJson['id_colaborador_ponto_coleta'],
            $idUsuario
        );

        $colaboradoresDocumentos = new ColaboradorDocumento();
        $colaboradoresDocumentos->id_colaborador = $idColaborador;
        $colaboradoresDocumentos->tipo_documento = 'CARTEIRA_HABILITACAO';
        $colaboradoresDocumentos->url_documento = FotosRepository::salvarFotoAwsS3(
            $_FILES['foto_documento_habilitacao'],
            "FOTO_HABILITACAO_ENTREGADOR_{$_ENV['AMBIENTE']}_{$idColaborador}_" . rand(),
            'ARQUIVOS_PRIVADOS',
            true
        );
        $colaboradoresDocumentos->save();

        $colaboradoresDocumentos = new ColaboradorDocumento();
        $colaboradoresDocumentos->id_colaborador = $idColaborador;
        $colaboradoresDocumentos->tipo_documento = 'REGISTRO_VEICULO';
        $colaboradoresDocumentos->url_documento = FotosRepository::salvarFotoAwsS3(
            $_FILES['foto_documento_veiculo'],
            "FOTO_REGISTRO_VEICULO_{$_ENV['AMBIENTE']}_{$idColaborador}_" . rand(),
            'ARQUIVOS_PRIVADOS',
            true
        );
        $colaboradoresDocumentos->save();

        DB::commit();
    }

    public function buscaRaios()
    {
        $raios = TransportadoresRaio::buscaRaiosDetalhadosDoColaborador();

        return $raios;
    }
}
