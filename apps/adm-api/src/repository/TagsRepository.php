<?php

namespace MobileStock\repository;

use MobileStock\helper\DB;
use MobileStock\helper\GeradorSql;
use MobileStock\model\Tag;
use MobileStock\model\TagTipo;
use PDO;

class TagsRepository
{
	public static function buscaTags(): array
	{
		return DB::select('SELECT tags.id, tags.nome FROM tags');
	}

	public static function salva(\PDO $conexao, Tag $tagObj): void
	{
		$geradorSql = new GeradorSql($tagObj);
		$sql = $geradorSql->insertFromDual(['nome']);

		$conexao->exec($sql);

		$tagObj->setId(self::buscaIdTagPorNome($conexao, $tagObj->getNome()));
	}

	private static function buscaIdTagPorNome(\PDO $conexao, string $nome): int
	{
		return DB::select('SELECT tags.id FROM tags WHERE tags.nome = ?', [
			$nome
		], $conexao, 'fetch')['id'];
	}

	public static function buscaTagsTipos(\PDO $conexao): array
	{
		$sql = $conexao->prepare(
			"SELECT
				tags_tipos.id_tag,
				tags_tipos.tipo,
				tags.nome
			FROM tags
			INNER JOIN tags_tipos ON tags_tipos.id_tag = tags.id
			ORDER BY tags_tipos.ordem DESC;"
		);
		$sql->execute();
		$tags = $sql->fetchAll(PDO::FETCH_ASSOC);

		$tagsMateriais = array_values(array_filter($tags, function ($item) {
			return $item['tipo'] === 'MA';
		}));
		$tagsCores = array_values(array_filter($tags, function ($item) {
			return $item['tipo'] === 'CO';
		}));

		$tagsCores = array_map(function (array $tagsCor): array {
			$tagsCor["nome"] = (string) preg_replace("/_/", " ", $tagsCor["nome"]);

			return $tagsCor;
		}, $tagsCores);

		return [
			'materiais' => $tagsMateriais,
			'cores' => $tagsCores
		];
	}

	public static function salvaComTipo(\PDO $conn, Tag $tag, string $tipo): void
	{
		self::salva($conn, $tag);
		$tag = new TagTipo($tag->getId(), $tipo);
		$geradorSql = new GeradorSql($tag);
		$sql = $geradorSql->insert();
		$conn->prepare($sql)->execute($geradorSql->bind);
	}

	public function removeTiposTag(\PDO $conn, int $idTag): void
	{
		$conn->prepare('DELETE FROM tags_tipos WHERE id_tag = ?')->execute([$idTag]);
	}
}