<?php

namespace api_administracao\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\Municipio;
use MobileStock\service\ConfiguracaoService;

class TaxasFrete
{
    public function consultaTaxasFrete()
    {
        $configuracoes = ConfiguracaoService::consultaTaxasFrete();
        return json_decode($configuracoes);
    }

    public function atualizaTaxasFrete()
    {
        DB::beginTransaction();
        $dados = Request::all();

        Validador::validar($dados, [
            'taxas' => [Validador::OBRIGATORIO],
        ]);

        foreach ($dados['taxas'] as $index => $taxa) {
            if ($index > 0 && $taxa['de'] <= $dados['taxas'][$index - 1]['ate']) {
                throw new \Exception('Valor inicial não pode ser menor ou igual ao valor final anterior');
            }
            if ($index < count($dados['taxas']) - 1 && $taxa['ate'] >= $dados['taxas'][$index + 1]['de']) {
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
        $configuracaoService->atualizaTaxasFrete($dados['taxas']);
        DB::commit();
        return ['message' => 'Taxas de frete atualizadas com sucesso!'];
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

            $dadosDaCidade->valor_frete = $taxa['valor_frete'];
            $dadosDaCidade->valor_adicional = $taxa['valor_adicional'];
            $dadosDaCidade->dias_entregar_frete = $taxa['dias_entregar_frete'];
            if (!empty($taxa['id_colaborador_transportador'])) {
                $dadosDaCidade->id_colaborador_ponto_coleta = $taxa['id_colaborador_transportador'];
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
