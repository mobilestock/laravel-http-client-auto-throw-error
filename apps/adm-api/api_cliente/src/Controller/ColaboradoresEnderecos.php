<?php

namespace api_cliente\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\Estado;
use MobileStock\model\Origem;
use MobileStock\service\IBGEService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ColaboradoresEnderecos
{
    public function autoCompletarEnderecoDigitado()
    {
        $dados = Request::all();

        Validador::validar($dados, [
            'endereco' => [Validador::OBRIGATORIO],
            'cidade_estado' => [],
        ]);

        $enderecoRequest = $dados['endereco'];
        $cidadeEstadoRequest = $dados['cidade_estado'] ?? '';
        $pesquisa = '';
        $dadosEndereco = [];
        $pesquisaPorCep =
            preg_match('/^[0-9]{5}-[0-9]{3}$/', $enderecoRequest) || preg_match('/^[0-9]{8}$/', $enderecoRequest);

        if ($pesquisaPorCep) {
            $dadosEndereco = IBGEService::buscaDadosEnderecoApiViaCep($enderecoRequest);
            if (!isset($dadosEndereco['cep'])) {
                throw new BadRequestHttpException('CEP inválido!');
            }
            $pesquisa = "{$dadosEndereco['logradouro']}, {$dadosEndereco['bairro']}, {$dadosEndereco['localidade']} - {$dadosEndereco['uf']}";
            $pesquisa .= " ({$dadosEndereco['cep']})";
        } else {
            $pesquisa = $enderecoRequest . ' ' . $cidadeEstadoRequest;
        }

        $enderecoPesquisa = [];
        $dadosEnderecoCliente = IBGEService::buscaDadosEnderecoApiGoogle($pesquisa);
        $dadosEnderecoCliente2 = IBGEService::buscaDadosEnderecoApiGoogle($enderecoRequest);
        $enderecoPesquisa['results'] = array_merge($dadosEnderecoCliente['results'], $dadosEnderecoCliente2['results']);

        if (!in_array($dadosEnderecoCliente['status'], ['OK', 'ZERO_RESULTS'])) {
            throw new BadRequestHttpException('Não foi encontrado nenhum endereço');
        }

        $resultado = array_map(function ($item) use ($dadosEndereco, $pesquisaPorCep) {
            $endereco = [];

            foreach ($item['address_components'] as $componente) {
                if (in_array('route', $componente['types'])) {
                    $endereco['logradouro'] = $componente['long_name'];
                }
                if (
                    in_array('street_number', $componente['types']) &&
                    !$pesquisaPorCep &&
                    !preg_match('/[^0-9]/', $componente['long_name'])
                ) {
                    $endereco['numero'] = $componente['long_name'];
                }
                if (in_array('sublocality_level_1', $componente['types'])) {
                    $endereco['bairro'] = $componente['long_name'];
                }
                if (in_array('administrative_area_level_2', $componente['types'])) {
                    $endereco['cidade'] = $componente['long_name'];
                }
                if (in_array('administrative_area_level_1', $componente['types'])) {
                    $endereco['uf'] = $componente['short_name'];
                }
                if (in_array('postal_code', $componente['types'])) {
                    $endereco['cep'] = !empty($dadosEndereco['cep']) ? $dadosEndereco['cep'] : $componente['long_name'];
                    $endereco['cep'] = preg_replace('/\D/', '', $endereco['cep']);
                }
            }

            if (!empty($endereco['cidade']) && !empty($endereco['uf'])) {
                $endereco['idCidade'] = IBGEService::buscarIDCidade(DB::getPdo(), $endereco['cidade'], $endereco['uf']);
            }

            $endereco['endereco_formatado'] = $item['formatted_address'];

            return $endereco;
        }, $enderecoPesquisa['results']);

        if (!empty($cidadeEstadoRequest) && !$pesquisaPorCep) {
            $cidade = mb_substr($cidadeEstadoRequest, 0, -3);
            $estado = mb_substr($cidadeEstadoRequest, -2);
            $resultado = array_filter($resultado, function ($item) use ($cidade, $estado) {
                return $item['cidade'] === $cidade || $item['uf'] === $estado;
            });

            $resultado = array_values(array_unique($resultado, SORT_REGULAR));
        }

        return $resultado;
    }

    public function novoEndereco(Origem $origem)
    {
        DB::beginTransaction();

        $dados = Request::all();

        $dados['telefone_destinatario'] = Request::telefone('telefone_destinatario');

        Validador::validar($dados, [
            'apelido' => [Validador::SE(Validador::OBRIGATORIO, Validador::TAMANHO_MAXIMO(50))],
            'nome_destinatario' => [Validador::OBRIGATORIO, Validador::TAMANHO_MAXIMO(255)],
            'eh_endereco_padrao' => [Validador::SE(Validador::OBRIGATORIO, Validador::BOOLEANO)],
            'endereco' => [Validador::OBRIGATORIO, Validador::TAMANHO_MAXIMO(255)],
            'numero' => [Validador::OBRIGATORIO, Validador::TAMANHO_MAXIMO(20)],
            'complemento' => [Validador::SE(Validador::OBRIGATORIO, Validador::TAMANHO_MAXIMO(255))],
            'ponto_de_referencia' => [Validador::SE(Validador::OBRIGATORIO, Validador::TAMANHO_MAXIMO(255))],
            'bairro' => [Validador::SE(Validador::OBRIGATORIO, Validador::TAMANHO_MAXIMO(255))],
            'id_cidade' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $cidade = IBGEService::buscarInfoCidade($dados['id_cidade']);

        $pesquisa = "{$dados['endereco']}, {$dados['numero']} {$dados['bairro']}, {$cidade['nome']} - {$cidade['uf']}";

        $dadosEnderecoCliente = IBGEService::buscaDadosEnderecoApiGoogle($pesquisa)['results'][0];

        $idColaborador = $origem->ehAdm() ? $dados['id_colaborador'] : Auth::user()->id_colaborador;

        ColaboradorEndereco::removerEnderecoNaoVerificado($idColaborador);

        $endereco = new ColaboradorEndereco();
        $endereco->id_colaborador = $idColaborador;
        $endereco->id_cidade = $dados['id_cidade'];
        $endereco->apelido = $dados['apelido'] ?? null;
        $endereco->nome_destinatario = $dados['nome_destinatario'];
        $endereco->telefone_destinatario = $dados['telefone_destinatario'];
        $endereco->esta_verificado = true;
        $endereco->eh_endereco_padrao = $dados['eh_endereco_padrao'] ?? false;
        $endereco->cep = $dados['cep'] ?? null;
        $endereco->logradouro = $dados['endereco'];
        $endereco->numero = $dados['numero'];
        $endereco->complemento = $dados['complemento'] ?? null;
        $endereco->ponto_de_referencia = $dados['ponto_de_referencia'] ?? null;
        $endereco->bairro = $dados['bairro'];
        $endereco->cidade = $cidade['nome'];
        $endereco->uf = $cidade['uf'];
        $endereco->latitude = $dadosEnderecoCliente['geometry']['location']['lat'];
        $endereco->longitude = $dadosEnderecoCliente['geometry']['location']['lng'];
        $endereco->save();

        DB::commit();
    }

    public function listarEnderecos(Origem $origem, ?int $idColaborador = null)
    {
        $idColaborador = $origem->ehAdm() ? $idColaborador : Auth::user()->id_colaborador;

        $enderecos = ColaboradorEndereco::listarEnderecos($idColaborador);

        return $enderecos;
    }

    public function excluirEndereco(int $idEndereco, Origem $origem)
    {
        DB::beginTransaction();

        $dados = Request::all();

        $idColaborador = (int) ($origem->ehAdm() ? $dados['id_colaborador'] : Auth::user()->id_colaborador);

        $endereco = ColaboradorEndereco::buscarEndereco($idEndereco);

        if (empty($endereco)) {
            throw new NotFoundHttpException('Endereço não encontrado');
        }

        if ($endereco->id_colaborador !== $idColaborador) {
            throw new AccessDeniedHttpException('Você não tem autorização para excluir este endereço');
        }

        if ($endereco->eh_endereco_padrao) {
            throw new BadRequestHttpException('Não é possível excluir o endereço padrão');
        }

        $endereco->delete();

        DB::commit();
    }

    public function buscarEndereco(int $idEndereco)
    {
        $enderecos = ColaboradorEndereco::listarEnderecos(Auth::user()->id_colaborador, $idEndereco);

        return $enderecos;
    }

    public function definirEnderecoPadrao(Origem $origem)
    {
        DB::beginTransaction();

        $dados = Request::all();

        Validador::validar($dados, [
            'id_endereco' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $idColaborador = $origem->ehAdm() ? $dados['id_colaborador'] : Auth::user()->id_colaborador;

        $colaboradoresEndereco = new ColaboradorEndereco();
        $colaboradoresEndereco->definirEnderecoPadrao($dados['id_endereco'], $idColaborador);

        DB::commit();
    }

    public function buscaEstados()
    {
        $estados = Estado::buscaEstados();
        return $estados;
    }
}
