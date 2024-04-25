<?php

namespace MobileStock\repository;

use MobileStock\database\Conexao;
use MobileStock\helper\GeradorSql;
use MobileStock\model\Categoria;
use PDO;

class CategoriasRepository
{
	public static function CategoriasCadastradas(): array
	{
		$categoriasCadastradas = Conexao::criarConexao()->query(
			"SELECT 
			categorias.id, 
			categorias.nome, 
			categorias.mostrar_altura_salto, 
			categorias.subcategoria, 
			categorias.id_categoria_pai, 
			COALESCE(categorias.tags, '') tags, 
			CONCAT('" . $_ENV['URL_MOBILE'] . "images/', categorias.icone_imagem) icone ,
			CONCAT('" . $_ENV['URL_MOBILE'] . "images/', categorias.icone_imagem) icone_imagem 
			FROM categorias ORDER BY categorias.ordem DESC")
			->fetchAll(PDO::FETCH_ASSOC);

		return $categoriasCadastradas;
	}
	public static function buscaArvoreCategorias(): array
	{
		$categoriasCadastradas = CategoriasRepository::CategoriasCadastradas();

		$salvaLista = [];

		foreach($categoriasCadastradas as $item) {

			$item['tags'] = array_filter(explode(',', $item['tags']));
			if($item['id_categoria_pai'] === null) {
				array_push($salvaLista, self::montaArvore($item, $categoriasCadastradas));
			}
		}

		return $salvaLista;
	}

	public static function montaArvore(array $item, array $categoriasCadastradas): array
	{

		foreach($categoriasCadastradas as $testeandoDnv) {
			if($testeandoDnv['id_categoria_pai'] === $item['id']) {
				if(!isset($item['children'])) $item['children'] = [];
				$testeandoDnv['tags'] = array_filter(explode(',', $testeandoDnv['tags']));
				array_push($item['children'], $testeandoDnv);
			}
		}

		if(isset($item['children']))
			foreach($item['children'] as $key => $toAquDnv)
				$item['children'][$key] = self::montaArvore($toAquDnv, $categoriasCadastradas);


		return $item;
	}

	public static function salva(PDO $conexao, Categoria $categoria): void
	{
		$geradorSql = new GeradorSql($categoria);
		$sql = $categoria->getId() > 0 ? $geradorSql->update() : $geradorSql->insert();

		$stmt = $conexao->prepare($sql);
		$stmt->execute($geradorSql->bind);

		$categoria->setId($conexao->lastInsertId());
	}

	public static function deleta(PDO $conn, Categoria $categoria): void
	{
		$geradorSql = new GeradorSql($categoria);
		$sql = $geradorSql->delete();

		$conn->prepare($sql)->execute($geradorSql->bind);
	}

	public static function categoriaTemPai(PDO $conexao, string $idCategoria): int
	{
		$PDOStatement = $conexao->prepare('SELECT id_categoria_pai FROM categorias WHERE id = ?');
		$PDOStatement->execute([$idCategoria]);

		$idCategoriaPai = $PDOStatement->fetchColumn();
		return is_null($idCategoriaPai) ? 0 : $idCategoria;
	}

	public static function insereFoto(array $arquivos, string $nomeCategoria): string
	{
		$extensao   = substr($arquivos['name'], strripos($arquivos['name'], '.'));
		$img_extensao = array('.svg');
		if (!in_array($extensao, $img_extensao)) { // valida extensão da imagem.
			throw new \InvalidArgumentException('Extensão não permitida para foto. O arquivo deve ser do tipo SVG');
		}
		$nomeimagem = strtolower($nomeCategoria) . $extensao;
		echo file_get_contents($arquivos['tmp_name']);
		$content = ob_get_clean();
		$diretorio  =  __DIR__ . '/../../images/' . $nomeimagem;
		file_put_contents($diretorio, $content);
		return $nomeimagem;
	}

	public static function listaCategoriasTipos(\PDO $conexao): array
	{
		$stmt = $conexao->prepare(
			"SELECT
				categorias.id,
				categorias.nome
			FROM categorias
			ORDER BY categorias.nome"
		);
		$stmt->execute();
		$consulta = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		return $consulta;
	}
}