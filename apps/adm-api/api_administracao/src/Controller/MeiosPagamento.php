<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use MobileStock\helper\Validador;
use MobileStock\service\ConfiguracaoService;

class MeiosPagamento extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = self::AUTENTICACAO;
        parent::__construct();
    }

    public function consultaMeiosPagamento()
    {
        try {
            $this->retorno['data']['meios_pagamento'] = ConfiguracaoService::consultaInfoMeiosPagamento($this->conexao);
        } catch (\Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao buscar meios de pagamento: ' . $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
        }
    }

    public function atualizaMeiosPagamento()
    {
        try {
            Validador::validar(['json' => $this->json], [
                'json' => [Validador::JSON]
            ]);

            $dadosJson = json_decode($this->json, true);
            Validador::validar($dadosJson, [
                'meios_pagamento' => [Validador::ARRAY]
            ]);

            $meiosPagamento = ConfiguracaoService::consultaInfoMeiosPagamento($this->conexao);

            $reduceApenasMeios = function (array $total, array $metodoPagamento) {
                foreach ($metodoPagamento['meios_pagamento'] as $meioPagamento) {
                    Validador::validar($meioPagamento, [
                        'situacao' => [Validador::OBRIGATORIO],
                        'local_pagamento' => [Validador::OBRIGATORIO]
                    ]);
                }

                return [...$total, ...$metodoPagamento['meios_pagamento']];
            };

            $apenasMeios = array_reduce($meiosPagamento, $reduceApenasMeios, []);
            $apenasMeiosRequisicao = array_reduce($dadosJson['meios_pagamento'], $reduceApenasMeios, []);

            if (count($meiosPagamento) !== count($dadosJson['meios_pagamento']) || count($apenasMeios) !== count($apenasMeiosRequisicao)) {
                throw new \InvalidArgumentException('Não pode alterar quantidade de meios de pagamento.');
            }

            ConfiguracaoService::atualizaMeiosPagamento($this->conexao, $dadosJson['meios_pagamento']);

            $this->retorno['data']['meios_pagamento'] = ConfiguracaoService::consultaInfoMeiosPagamento($this->conexao);
        } catch (\Throwable $exception) {
            $this->retorno['status'] = false;
            $this->retorno['message'] = 'Ocorreu um erro ao atualizar os meios de pagamento: ' . $exception->getMessage();
            $this->codigoRetorno = 400;
        } finally {
            $this->respostaJson->setData($this->retorno)->setStatusCode($this->codigoRetorno)->send();
        }
    }
}