<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\TransportadoresRaio;
use MobileStock\model\UsuarioModel;
use MobileStock\repository\ColaboradoresRepository;
use MobileStock\service\IBGEService;
use MobileStock\service\TipoFreteService;
use MobileStock\service\TransporteService;

class Entregadores extends Request_m
{
    public function buscaListaEntregadores()
    {
        $entregadores = TransporteService::buscaListaEntregadores();

        return $entregadores;
    }

    public function buscaDocumentosEntregador(array $dados)
    {
        try {
            Validador::validar($dados, [
                'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $pontoParado = $this->request->query->getBoolean('ponto_parado');

            $this->retorno['data'] = TransporteService::buscaDocumentosEntregador(
                $this->conexao,
                $dados['id_colaborador'],
                $pontoParado
            );

            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Buscado com sucesso!';
            $this->codigoRetorno = 200;
        } catch (\Throwable $ex) {
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function gerirPontoColeta()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar(
                ['json' => $this->json],
                [
                    'json' => [Validador::JSON],
                ]
            );

            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'id_colaborador_ponto' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            $pontoAtivo = IBGEService::buscaStatusPontoColeta($this->conexao, $dadosJson['id_colaborador_ponto']);
            IBGEService::gerenciarPontoColeta(
                $this->conexao,
                $dadosJson['id_colaborador_ponto'],
                !$pontoAtivo,
                $this->idUsuario
            );

            $this->conexao->commit();
            $this->retorno['data'] = !$pontoAtivo;
            $this->retorno['message'] = 'Ponto de coleta alterado com sucesso!';
        } catch (\Throwable $ex) {
            $this->conexao->rollback();
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }
    public function mudaSituacao()
    {
        DB::beginTransaction();

        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'id' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_usuario' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'situacao' => [Validador::OBRIGATORIO, Validador::ENUM('PE', 'ML')],
        ]);

        $entregador = new TipoFreteService();
        $entregador->id = (int) $dadosJson['id'];
        $entregador->categoria = $dadosJson['situacao'];
        $entregador->alteraCategoriaTipoFrete();

        if ($dadosJson['situacao'] === 'ML') {
            UsuarioModel::adicionarPermissao($dadosJson['id_usuario'], 62);
            ColaboradoresRepository::removePermissaoUsuario($dadosJson['id_usuario'], ['60']);
        }

        if ($dadosJson['situacao'] === 'PE') {
            $idColaborador = UsuarioModel::buscaIdColaboradorPorIdUsuario($dadosJson['id_usuario']);
            IBGEService::gerenciarPontoColeta(DB::getPdo(), $idColaborador, false, Auth::user()->id);
        }
        DB::commit();
    }

    public function atualizarPontoColeta(array $parametros)
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($parametros, [
                'id_colaborador_entregador' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_colaborador_coleta' => [Validador::OBRIGATORIO, Validador::NUMERO],
            ]);

            TipoFreteService::adicionaCentralColeta(
                $this->conexao,
                $parametros['id_colaborador_entregador'],
                $parametros['id_colaborador_coleta'],
                $this->idUsuario
            );

            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Ponto de coleta alterado com sucesso!';
            $this->codigoRetorno = 200;

            $this->conexao->commit();
        } catch (\Throwable $ex) {
            $this->conexao->rollback();
            $this->codigoRetorno = 400;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function atualizarRaios()
    {
        DB::beginTransaction();

        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'raios' => [Validador::OBRIGATORIO, Validador::ARRAY],
        ]);

        foreach ($dadosJson['raios'] as $raio) {
            Validador::validar($raio, [
                'id_raio' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'raio' => [Validador::NUMERO, Validador::NAO_NULO],
                'latitude' => [Validador::LATITUDE],
                'longitude' => [Validador::LONGITUDE],
            ]);

            $transportadoresRaio = new TransportadoresRaio();
            $transportadoresRaio->exists = true;
            $transportadoresRaio->id = $raio['id_raio'];
            $transportadoresRaio->raio = $raio['raio'];
            $transportadoresRaio->latitude = $raio['latitude'];
            $transportadoresRaio->longitude = $raio['longitude'];
            $transportadoresRaio->update();
        }
        DB::commit();
    }

    public function adicionarCidade()
    {
        DB::beginTransaction();

        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'id_colaborador' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'id_cidade' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'preco_entrega' => [Validador::NUMERO, Validador::NAO_NULO],
        ]);

        $cidade = IBGEService::buscarInfoCidade($dadosJson['id_cidade']);
        TransportadoresRaio::create([
            'id_colaborador' => $dadosJson['id_colaborador'],
            'id_cidade' => $dadosJson['id_cidade'],
            'preco_entrega' => $dadosJson['preco_entrega'],
            'latitude' => $cidade['latitude'],
            'longitude' => $cidade['longitude'],
        ]);

        DB::commit();
    }

    public function atualizarDadosRaioEntregador()
    {
        DB::beginTransaction();
        $dadosJson = Request::all();
        Validador::validar($dadosJson, [
            'id_raio' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'apelido' => [Validador::SE(Validador::OBRIGATORIO, [Validador::TAMANHO_MAXIMO(50)])],
            'preco_entrega' => [Validador::NUMERO, Validador::NAO_NULO],
            'preco_coleta' => [Validador::NUMERO, Validador::NAO_NULO],
            'esta_ativo' => [Validador::NAO_NULO, Validador::BOOLEANO],
            'prazo_forcar_entrega' => [Validador::OBRIGATORIO, Validador::NUMERO],
            'dias_margem_erro' => [Validador::NAO_NULO, Validador::NUMERO],
            'dias_entregar_cliente' => [Validador::NAO_NULO, Validador::NUMERO],
        ]);

        $transportadoresRaio = new TransportadoresRaio();
        $transportadoresRaio->exists = true;
        $transportadoresRaio->id = $dadosJson['id_raio'];
        $transportadoresRaio->apelido = $dadosJson['apelido'] ?: null;
        $transportadoresRaio->preco_entrega = $dadosJson['preco_entrega'];
        $transportadoresRaio->preco_coleta = $dadosJson['preco_coleta'];
        $transportadoresRaio->esta_ativo = $dadosJson['esta_ativo'];
        $transportadoresRaio->prazo_forcar_entrega = $dadosJson['prazo_forcar_entrega'];
        $transportadoresRaio->dias_margem_erro = $dadosJson['dias_margem_erro'];
        $transportadoresRaio->dias_entregar_cliente = $dadosJson['dias_entregar_cliente'];
        $transportadoresRaio->update();

        DB::commit();
    }

    public function atualizarStatusRaio(int $idRaio)
    {
        DB::beginTransaction();

        $transportadoresRaio = TransportadoresRaio::buscaInformacoesPorIdRaio($idRaio);
        $transportadoresRaio->esta_ativo = !$transportadoresRaio->esta_ativo;
        $transportadoresRaio->update();

        DB::commit();
    }
}
