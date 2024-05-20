<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\database\Conexao;
use MobileStock\helper\Validador;
use MobileStock\model\Municipio;
use MobileStock\service\ConfiguracaoService;

class TaxasFrete extends Request_m
{
    public function __construct()
    {
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }

    public function consultaTaxasFrete()
    {
        try {
            $this->retorno['data'] = ConfiguracaoService::consultaTaxasFrete($this->conexao);
        } catch (\Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao consultar taxas de frete: ' . $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function atualizaTaxasFrete()
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
                'taxas' => [Validador::JSON, Validador::OBRIGATORIO],
            ]);

            $dadosJson['taxas'] = array_map(function ($taxa) {
                $taxa['de'] = (int) $taxa['de'];
                $taxa['ate'] = (int) $taxa['ate'];
                $taxa['porcentagem'] = (int) $taxa['porcentagem'];
                return $taxa;
            }, $dadosJson['taxas']);

            foreach ($dadosJson['taxas'] as $index => $taxa) {
                if ($index > 0 && $taxa['de'] <= $dadosJson['taxas'][$index - 1]['ate']) {
                    throw new \Exception('Valor inicial não pode ser menor ou igual ao valor final anterior');
                }
                if ($index < count($dadosJson['taxas']) - 1 && $taxa['ate'] >= $dadosJson['taxas'][$index + 1]['de']) {
                    throw new \Exception('Valor final não pode ser maior ou igual ao valor inicial seguinte');
                }
                if ($taxa['de'] > $taxa['ate']) {
                    throw new \Exception('O valor inicial não pode ser maior que o valor final!');
                }
                if ($taxa['ate'] < $taxa['de']) {
                    throw new \Exception('O valor final não pode ser menor que o valor inicial!');
                }
                if ($taxa['de'] == $taxa['ate']) {
                    throw new \Exception('O valor inicial não pode ser igual ao valor final!');
                }
            }

            $configuracaoService = new ConfiguracaoService();
            $configuracaoService->atualizaTaxasFrete($this->conexao, $dadosJson['taxas']);
            $this->conexao->commit();
            $this->retorno['message'] = 'Taxas de frete atualizadas com sucesso!';
            $this->codigoRetorno = 200;
        } catch (\Throwable $exception) {
            $this->conexao->rollBack();
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao atualizar as taxas de frete: ' . $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->codigoRetorno)
                ->send();
        }
    }

    public function atualizaFretesPorCidade()
    {
        DB::beginTransaction();

        $dadosJson = Request::all();

        Validador::validar($dadosJson, [
            'valores' => [Validador::ARRAY, Validador::OBRIGATORIO],
        ]);

        foreach ($dadosJson['valores'] as $taxa) {
            Validador::validar($taxa, [
                'id' => [Validador::NUMERO, Validador::OBRIGATORIO],
                'valor_frete' => [Validador::NUMERO],
                'valor_adicional' => [Validador::NUMERO],
                'dias_entregar_frete' => [Validador::OBRIGATORIO, Validador::NUMERO],
                'id_colaborador_transportador' => [Validador::SE(Validador::OBRIGATORIO, Validador::NUMERO)],
            ]);

            $dadosDaCidade = Municipio::buscaCidade($taxa['id']);
            if (
                $dadosDaCidade->valor_frete === (float) $taxa['valor_frete'] &&
                $dadosDaCidade->valor_adicional === (float) $taxa['valor_adicional'] &&
                $dadosDaCidade->dias_entregar_frete === (int) $taxa['dias_entregar_frete'] &&
                $dadosDaCidade->id_colaborador_transportador === (int) $taxa['id_colaborador_transportador']
            ) {
                continue;
            }

            $dadosDaCidade->valor_frete = $taxa['valor_frete'];
            $dadosDaCidade->valor_adicional = $taxa['valor_adicional'];
            $dadosDaCidade->dias_entregar_frete = $taxa['dias_entregar_frete'];
            if (!empty($taxa['id_colaborador_transportador'])) {
                $dadosDaCidade->id_colaborador_transportador = $taxa['id_colaborador_transportador'];
            }
            $dadosDaCidade->update();
        }

        DB::commit();
    }

    public function buscaFretesPorEstado(string $estado)
    {
        $fretes = Municipio::buscaFretes($estado);
        return $fretes;
    }
}
