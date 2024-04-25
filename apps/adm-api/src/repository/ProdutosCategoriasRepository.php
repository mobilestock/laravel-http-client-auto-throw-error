<?php

namespace MobileStock\repository;

use MobileStock\helper\GeradorSql;
use MobileStock\model\ProdutosCategorias;

class ProdutosCategoriasRepository
{
	public static function salva(\PDO $conn, ProdutosCategorias $produtosCategorias): void
	{
		$geradorSql = new GeradorSql($produtosCategorias);
		$sql = $geradorSql->insertFromDual(['id_produto', 'id_categoria']);

		$conn->prepare($sql)->execute($geradorSql->bind);

		$produtosCategorias->setId($conn->lastInsertId());
	}

	public static function removeCategoriasProduto(\PDO $conn, int $id): void
	{
		$conn
			->prepare('DELETE FROM produtos_categorias WHERE id_produto = ?')
			->execute([$id]);
	}

}