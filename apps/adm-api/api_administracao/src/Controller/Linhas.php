<?php

namespace api_administracao\Controller;

use api_administracao\Models\Request_m;
use MobileStock\repository\ProdutosRepository;

class Linhas extends Request_m
{
	public function listarLinhas()
	{
		$this->respostaJson->setData([
			'status' => true,
			'message' => 'Linhas buscadas com sucesso!',
			'data' => [
				'linhas' => ProdutosRepository::buscaLinhas($this->conexao)
			]
		])->send();
	}
}
