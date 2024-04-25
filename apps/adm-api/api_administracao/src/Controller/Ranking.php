<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use Exception;
use MobileStock\helper\Validador;
use MobileStock\service\Ranking\RankingService;

class Ranking extends Request_m
{
//    public function listarPremiacoes()
//    {
//			try {
//				$data = $this->request->get('data');
//				$ranking = $this->request->get('ranking');
//
//				$dataApi = null;
//
//				if ($data && $ranking) {
//					$data = new DateTime($data);
//					$dataApi = RankingVencedoresItensService::buscarListaPremiacoesFiltradas(
//						$this->conexao,
//						$data->format('Y-m-d'),
//						$ranking
//					);
//					$premios = RankingService::buscaRankingPremiosPorChave($this->conexao, $ranking, sizeof($dataApi));
//
//					foreach ($dataApi as $index => $_) {
//						if ($index < sizeof($premios)) {
//							$dataApi[$index]['porcentagem'] = $premios[$index]['porcentagem'] ?? 0;
//							$dataApi[$index]['premio_total'] = round($dataApi[$index]['valor_final'] * ($premios[$index]['porcentagem'] / 100), 2);
//						} else {
//							$dataApi[$index]['porcentagem'] = 0;
//							$dataApi[$index]['premio_total'] = 0;
//						}
//					}
//				} else {
//					$dataApi = RankingVencedoresItensService::buscaListaPremiacoes($this->conexao);
//				}
//
//				$this->retorno['data'] = $dataApi;
//				$this->retorno['message'] = 'Premiações buscadas com sucesso!';
//				$this->status = 200;
//			} catch (Exception $ex) {
//				$this->retorno['status'] = false;
//				$this->retorno['message'] = $ex->getMessage();
//				$this->status = 400;
//			} finally {
//				$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
//			}
//    }

//	public function listarVendasDoLancamento(array $dados)
//	{
//		try {
//			$this->retorno['data'] = RankingVencedoresItensService::buscarVendasLancamentoPendente($this->conexao, $dados['idLancamentoPendente']);
//			$this->retorno['message'] = 'Premiações buscadas com sucesso!';
//			$this->status = 200;
//		} catch (Exception $ex) {
//			$this->retorno['status'] = false;
//			$this->retorno['message'] = $ex->getMessage();
//			$this->status = 400;
//		} finally {
//			$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
//		}
//	}

	// public function buscarInfluencersOficiais()
	// {
	// 	try {
	// 		$this->retorno['data'] = RankingService::buscaColaboradoresCompartilhadoresLinkMeuLook($this->conexao);
	// 		$this->retorno['message'] = 'Influencers oficiais buscados com sucesso!';
    //         $this->status = 200;
	// 	} catch (Exception $ex) {
	// 		$this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
	// 		$this->status = 400;
	// 	} finally {
	// 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
	// 	}
	// }

	// public function alterarSituacaoInfluencerOficial(array $dados)
	// {
	// 	try {
	// 		Validador::validar($dados, ['id_usuario' => [Validador::OBRIGATORIO, Validador::NUMERO]]);
	// 		$this->conexao->beginTransaction();
	// 		$this->retorno['data']['situacao'] = RankingService::alterarSituacaoInfluencerOficial($this->conexao, $dados['id_usuario']);
	// 		$this->retorno['message'] = 'Influencer alterado com sucesso!';
    //         $this->status = 200;
	// 		$this->conexao->commit();

	// 	} catch (Exception $ex) {
	// 		$this->conexao->rollBack();
	// 		$this->retorno['status'] = false;
    //         $this->retorno['message'] = $ex->getMessage();
	// 		$this->status = 400;

	// 	} finally {
	// 		$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
	// 	}
	// }

//	public function listarPremiosAplicados()
//	{
//		try {
//			$this->retorno['data'] = RankingVencedoresItensService::listarPremiosAplicados($this->conexao);
//			$this->retorno['message'] = 'Prêmios buscados com sucesso!';
//			$this->retorno['status'] = true;
//			$this->status = 200;
//		} catch (Exception $ex) {
//			$this->retorno['status'] = false;
//            $this->retorno['message'] = $ex->getMessage();
//			$this->status = 400;
//		} finally {
//			$this->respostaJson->setData($this->retorno)->setStatusCode($this->status)->send();
//		}
//	}

}