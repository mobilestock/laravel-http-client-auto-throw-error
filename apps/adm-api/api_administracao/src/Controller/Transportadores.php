<?php

namespace api_administracao\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\TransportadoresRaio;
use MobileStock\model\UsuarioModel;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\IBGEService;
use MobileStock\service\MessageService;
use MobileStock\service\TipoFreteService;

class Transportadores
{
    public function atualizaSituacao()
    {
        DB::beginTransaction();

        $idUsuario = Auth::user()->id;
        $dadosJson = Request::all();
        $dadosJson['telefone'] = Request::telefone();
        Validador::validar($dadosJson, [
            'id_usuario_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_colaborador_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'tipo_ponto' => [Validador::OBRIGATORIO, Validador::ENUM('PP', 'PM')],
            'situacao' => [Validador::OBRIGATORIO, Validador::ENUM('APROVADO', 'REJEITADO')],
        ]);

        if ($dadosJson['situacao'] === 'REJEITADO') {
            TipoFreteService::rejeitaSolicitacaoPonto($dadosJson['id_colaborador_ponto']);
        } else {
            TipoFreteService::gerenciaSituacaoPonto($dadosJson['id_colaborador_ponto'], true);

            UsuarioModel::adicionarPermissao(
                $dadosJson['id_usuario_ponto'],
                $dadosJson['tipo_ponto'] === 'PP' ? 60 : 62
            );
            ColaboradoresRepository::removePermissaoUsuario(
                $dadosJson['id_usuario_ponto'],
                $dadosJson['tipo_ponto'] === 'PP' ? ['62'] : ['60']
            );
            if ($dadosJson['tipo_ponto'] === 'PP') {
                IBGEService::gerenciarPontoColeta(DB::getPdo(), $dadosJson['id_colaborador_ponto'], true, $idUsuario);
            }
        }

        if (
            $dadosJson['tipo_ponto'] === 'PP' ||
            ($dadosJson['tipo_ponto'] === 'PM' && $dadosJson['situacao'] === 'REJEITADO')
        ) {
            $raios = TransportadoresRaio::buscaRaiosDoColaboradorAtivosOuNao(
                $dadosJson['id_colaborador_ponto'],
                $dadosJson['situacao'] === 'APROVADO'
            );
            foreach ($raios as $idRaio) {
                $transportadoresRaio = new TransportadoresRaio();
                $transportadoresRaio->exists = true;
                $transportadoresRaio->id = $idRaio;
                $transportadoresRaio->esta_ativo = $dadosJson['situacao'] === 'APROVADO';
                $transportadoresRaio->update();
            }
        }

        if ($dadosJson['situacao'] === 'APROVADO') {
            $tipoPonto = $dadosJson['tipo_ponto'] === 'PP' ? 'ponto' : 'entregador';
            $mensagem =
                "Sua solicitação de cadastro de {$tipoPonto} do meulook foi aprovada!\n\n" .
                "Entre em contato com o suporte para mais informações.\n\n" .
                "Responda esta mensagem com a palavra 'SUPORTE' para receber o contato.";
            $msgService = new MessageService();
            $msgService->sendMessageWhatsApp($dadosJson['telefone'], $mensagem);
        }

        DB::commit();
    }
}
