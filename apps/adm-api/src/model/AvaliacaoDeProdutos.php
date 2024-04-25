<?php

// namespace MobileStock\model;

// use http\Exception\InvalidArgumentException;
// use PDO;
// use Exception;
// use Aws\S3\S3Client;
// use Aws\S3\Exception\S3Exception;
// use MobileStock\database\Conexao;
// use MobileStock\helper\Globals;

// class AvaliacaoDeProdutos
// {

//     private $idColaborador;
//     private $idProduto;
//     private $pontos;
//     private $forma;

//     /**
//      * Instancia cliente
//      */
//     public function setCliente(int $idColaborador): void
//     {
//         $this->idColaborador = $idColaborador;
//     }

//     private function salvaFotoAvaliacao($foto): string
//     {
//         $img_extensao = array('.jpg', '.JPG', '.jpge', '.JPGE');
//         $extensao = substr($foto['foto']['name'], strripos($foto['foto']['name'], '.'));


//         if ($foto['foto']['name'] == "" && !$foto['foto']['name']) //valida existencia de foto
//         {
//             throw new \InvalidArgumentException('Imagem inválida');
//         }

//         if (!in_array($extensao, $img_extensao)) // valida extensão da imagem.
//         {
//             throw new \InvalidArgumentException("Sistema permite apenas imagens com extensão '.jpg'.");
//         }

//         $nomeimagem = "Image" . rand(0, 100) . date('dmYhms') . $extensao;
//         $caminhoImagens = 'https://s3-sa-east-1.amazonaws.com/mobilestock-fotos/' . $nomeimagem;

//         $img = imagecreatefromjpeg($foto['foto']['tmp_name']);
//         $exif = exif_read_data($foto['foto']['tmp_name']);
//         if ($img && $exif && isset($exif['Orientation'])) {
//             $ort = $exif['Orientation'];

//             if ($ort == 6 || $ort == 5)
//                 $img = imagerotate($img, 270, null);
//             if ($ort == 3 || $ort == 4)
//                 $img = imagerotate($img, 180, null);
//             if ($ort == 8 || $ort == 7)
//                 $img = imagerotate($img, 90, null);

//             if ($ort == 5 || $ort == 4 || $ort == 7)
//                 imageflip($img, IMG_FLIP_HORIZONTAL);
//         }
//         imagejpeg($img, "../temp/" . $nomeimagem, 30);

//         try {
//             $s3 = new S3Client(Globals::S3_OPTIONS('AVALIACAO_DE_PRODUTOS'));
//         } catch (Exception $e) {
//             throw new \DomainException('Erro ao conectar com o servidor');
//         }

//         try {
//             $s3->putObject([
//                 'Bucket' => 'mobilestock-fotos',
//                 'Key' => $nomeimagem,
//                 'SourceFile' => "../temp/$nomeimagem"
//             ]);
//             $this->pontos += 20;
//         } catch (S3Exception $e) {
//             throw new \DomainException("Erro ao enviar imagem");
//         }

//         imagedestroy($img);
//         return $caminhoImagens;
//     }

//     /**
//      * Salva avaliação e retona a quantidade de ponto que o cliente ganhou
//      */
//     public function salvaAvaliacaoCliente(int $idProduto, int $qualidade, int $custoBeneficio, string $comentario, int $forma, array $foto = null): array
//     {
//         $conexao = Conexao::criarConexao();
//         $retorno = ['error' => '', 'success' => ''];

//         if ($this->verificaClienteAvaliouProduto(intval($idProduto))) {
//             $retorno['error'] = "Você já avaliou este produto.";
//             return $retorno;
//         }

//         if ($foto && count($foto) > 0) {

//             $caminhoImagens = $this->salvaFotoAvaliacao($foto);
//         } else {
//             $caminhoImagens = "";
//         }

//         if ($qualidade <= 0 || $qualidade > 5) {
//             throw new \InvalidArgumentException("Campo qualidade é obrigatório.");
//         }

//         if ($custoBeneficio <= 0 || $custoBeneficio > 5) {
//             throw new \InvalidArgumentException("Campo custo beneficio é obrigatório.");
//         }

//         if ($forma <= 0 || $forma > 3) {
//             throw new \InvalidArgumentException("Campo forma é obrigatório.");
//         }

//         $this->pontos += 30; // custo beneficio, forma e qualidade somam 30 pontos ao cliente

//         if (strlen($comentario) >= 5) {
//             $this->pontos += 10;
//         }

//         $query = $conexao->prepare("INSERT INTO avaliacao_produtos(id_cliente,id_produto,forma,qualidade,custo_beneficio,comentario,foto_upload)VALUES(:id_cliente,:id_produto,:forma,:qualidade,:custo_beneficio,:comentario,:upload);");
//         $query->bindParam(':id_cliente', $this->idColaborador, PDO::PARAM_INT);
//         $query->bindParam(':forma', $forma, PDO::PARAM_INT);
//         $query->bindParam(':comentario', $comentario, PDO::PARAM_STR, 180);
//         $query->bindParam(':id_produto', $idProduto, PDO::PARAM_INT);
//         $query->bindParam(':qualidade', $qualidade, PDO::PARAM_INT);
//         $query->bindParam(':upload', $caminhoImagens, PDO::PARAM_STR);
//         $query->bindParam(':custo_beneficio', $custoBeneficio, PDO::PARAM_INT);

//         if ($query->execute()) {
//             $this->inserePontos();
//             $this->insereHistoricoPontos($idProduto);
//             $retorno['success'] = $this->pontos;

//             return $retorno;
//         }

//         throw new \DomainException('Erro ao avaliar o produto');
//         return $retorno;
//     }

//     /**
//      * Salva avaliação interna de produto
//      */
//     public function salvaAvaliacaoVendedor(int $idProduto, int $qualidade, int $custoBeneficio, int $forma, string $comentario, $foto): array
//     {
//         $conexao = Conexao::criarConexao();
//         $retorno = ['error' => '', 'success' => ''];

//         if ($qualidade <= 0 || $qualidade > 5) {
//             $retorno['error'] = "Campo qualidade é obrigatório.";
//             return $retorno;
//         }

//         if ($custoBeneficio <= 0 || $custoBeneficio > 5) {
//             $retorno['error'] = "Campo custo beneficio é obrigatório.";
//             return $retorno;
//         }

//         if ($forma <= 0 || $forma > 3) {
//             $retorno['error'] = "Campo forma é obrigatório.";
//             return $retorno;
//         }

//         if ($foto && count($foto) > 0) {

//             $caminhoImagens = $this->salvaFotoAvaliacao($foto);
//         } else {
//             $caminhoImagens = "";
//         }

//         $query = $conexao->prepare("INSERT INTO avaliacao_produtos(id_colaborador,forma, id_produto, qualidade, custo_beneficio, comentario, foto_upload)
//             VALUES(:id_colaborador,:forma, :id_produto,:qualidade,:custo_beneficio, :comentario, :foto)");
//         $query->bindParam(':id_colaborador', $this->idColaborador, PDO::PARAM_INT);
//         $query->bindParam(':forma', $forma, PDO::PARAM_INT);
//         $query->bindParam(':id_produto', $idProduto, PDO::PARAM_INT);
//         $query->bindParam(':qualidade', $qualidade, PDO::PARAM_INT);
//         $query->bindParam(':custo_beneficio', $custoBeneficio, PDO::PARAM_INT);
//         $query->bindParam(':comentario', $comentario, PDO::PARAM_STR);
//         $query->bindParam(':foto', $caminhoImagens);
//         if ($query->execute()) {

//             //            $conexao2 = Conexao::criarConexao();

//             //          $query2 = $conexao2->prepare("UPDATE produtos SET forma = :forma WHERE id = :id");
//             //        $query2->bindParam(':forma', $this->forma, PDO::PARAM_INT);
//             //      $query2->bindParam(':id', $this->idProduto, PDO::PARAM_INT);

//             //    if ($query2->execute()) {r
//             $retorno['success'] = 'Produto avaliado com sucesso.';
//             return $retorno;
//             // }
//         }
//         $retorno['error'] = "Erro ao Avaliar produto";
//         return $retorno;
//     }

//     /**
//      * Retorna resumo das avaliações do produto
//      * @return array [ erro, estrelas_1,estrelas_2,estrelas_3,estrelas_4,estrelas_5, tamanhoPequeno,tamanhoNormal,tamanhoGrande, qualidade, custo_beneficio, avaliacoes]
//      */
//     public function buscaResumoProduto(int $idProduto): array
//     {
//         $this->idProduto = $idProduto;

//         if (!$itens = $this->calculaAvaliacaoMedia()) {
//             $retorno["erro"] = 1;
//             return $retorno;
//         }

//         $retorno["erro"] = 0;
//         $retorno['estrelas_1'] = $itens[0]['num_1'];
//         $retorno['estrelas_2'] = $itens[0]['num_2'];
//         $retorno['estrelas_3'] = $itens[0]['num_3'];
//         $retorno['estrelas_4'] = $itens[0]['num_4'];
//         $retorno['estrelas_5'] = $itens[0]['num_5'];
//         $retorno['tamanhoPequeno'] = 0;
//         $retorno['tamanhoNormal'] = 0;
//         $retorno['tamanhoGrande'] = 0;
//         if (intVal($itens[0]['tamanho_1']) != 0) {
//             $retorno['tamanhoPequeno'] = (intVal($itens[0]['tamanho_1']) * 100) / $itens[0]['avaliacoes'];
//         }
//         if (intVal($itens[0]['tamanho_2']) != 0) {
//             $retorno['tamanhoNormal'] = (intVal($itens[0]['tamanho_2']) * 100) / $itens[0]['avaliacoes'];
//         }
//         if (intVal($itens[0]['tamanho_3']) != 0) {
//             $retorno['tamanhoGrande'] = (intVal($itens[0]['tamanho_3']) * 100) / $itens[0]['avaliacoes'];
//         }
//         $retorno['qualidade'] = $itens[0]['media_qualidade'];
//         $retorno['custoBeneficio'] = $itens[0]['media_custo_beneficio'];

//         $retorno['avaliacoes'] = $itens[0]['avaliacoes'];

//         return $retorno;
//     }

//     /**
//      * Colaborador libera foto para a visualização ao publico
//      */
//     public function liberaFotoCliente(int $id): bool
//     {
//         $conexao = Conexao::criarConexao();
//         $query = $conexao->prepare("UPDATE avaliacao_produtos SET libera_foto = 1 WHERE id = :id");
//         $query->bindParam(':id', $id, PDO::PARAM_INT);

//         return $query->execute() or die(print_r($query->errorInfo(), true));
//     }

//     /**
//      * retorna um array com até 5 comentarios
//      * @return matriz [ id, nome, comentario, dataHora, qualidade, custoBeneficio, foto]
//      * @param int $pagina default 0 [retorna os primeiros comantários]
//      */
//     public function buscaComentariosProduto(int $produto, int $pagina = 0): array
//     {
//         $this->idProduto = $produto;
//         $retorno = array();

//         $itens = $this->busca5ComentariosProduto($pagina);

//         foreach ($itens as $item) {

//             $comentario = utf8_encode($item['comentario']);

//             if (strpos($comentario, 'Ã') !== false) {
//                 $comentario  = $item['comentario'];
//             }
//             array_push(
//                 $retorno,
//                 [
//                     "id" => $item["id"],
//                     "nome" => $item['razao_social'],
//                     "comentario" => $comentario,
//                     "dataHora" => $item['data_avaliacao'],
//                     "qualidade" => $item['qualidade'],
//                     "custoBeneficio" => $item['custo_beneficio'],
//                     "foto" => $item['foto_upload'],
//                     "vendedor" => $item['id_colaborador'] == 0 ? false : true,
//                 ]
//             );
//         }

//         return $retorno;
//     }

//     /**
//      * retorna um array contendo todos os comentarios e fotos deste produto
//      * @return matriz [ id, idCliente, nome, comentario, dataHora, qualidade, custoBeneficio, denuncias, foto, denunciaFoto, libera_foto]
//      */
//     public function buscaTodosComentarios(int $produto, int $pagina = 0): array
//     {
//         $this->idProduto = $produto;
//         $retorno = array();
//         if ($pagina) {
//             $itens = $this->buscaTodosComentariosProduto($pagina);
//         } else {
//             $itens = $this->buscaTodosComentariosProduto();
//         }

//         foreach ($itens as $item) {
//             $comentario = utf8_encode($item['comentario']);

//             if (strpos($comentario, 'Ã') !== false) {
//                 $comentario  = $item['comentario'];
//             }
//             array_push(
//                 $retorno,
//                 array(
//                     "id" => $item["id"],
//                     "idCliente" => $item["id_cliente"],
//                     "idColaborador" => $item['id_colaborador'],
//                     "nome" => $item['razao_social'],
//                     "comentario" => $comentario,
//                     "dataHora" => $item['data_avaliacao'],
//                     "qualidade" => $item['qualidade'],
//                     "custoBeneficio" => $item['custo_beneficio'],
//                     "denuncias" => $item['denuncia'],
//                     "foto" => $item['foto_upload'],
//                     "denunciaFoto" => $item['denuncia_foto']
//                 )
//             );
//         }

//         return $retorno;
//     }

//     /**
//      * exclui do banco de dados o comentario avaliado como abusivo
//      * @param int $idAvaliacao
//      * @param string $tipoUsuario
//      * @param $idUsuario
//      * @return bool
//      */
//     public function excluiComentarioAbusivo(int $idAvaliacao, string $tipoUsuario, $idUsuario): bool
//     {
//         $this->idColaborador = $idUsuario;
//         $tipoUsuario = 'id_' . $tipoUsuario;
//         $conexao = Conexao::criarConexao();
//         $query = $conexao->prepare('UPDATE avaliacao_produtos SET comentario = NULL WHERE id = :id AND ' . $tipoUsuario . ' = :idCliente');
//         $query->bindParam(':id', $idAvaliacao, PDO::PARAM_INT);
//         $query->bindParam(':idCliente', $this->idColaborador, PDO::PARAM_INT);
//         if ($query->execute()) {
//             return true;
//         }
//         return false;
//     }

//     /**
//      * exclui a foto do banco de dados e do bucket mobilestock-fotos 
//      */
//     public function excluiFotoAbusiva(int $idAvaliacao, string $urlFoto): bool
//     {
//         $conexao = Conexao::criarConexao();
//         $query = $conexao->prepare('UPDATE avaliacao_produtos SET foto_upload = NULL WHERE id = :id');
//         $query->bindParam(':id', $idAvaliacao, PDO::PARAM_INT);

//         if ($query->execute()) {
//             $nomeimagem = substr($urlFoto, 53);
//             try {
//                 $s3 = new S3Client(Globals::S3_OPTIONS('AVALIACAO_DE_PRODUTOS'));
//             } catch (Exception $e) {
//                 return false;
//             }

//             try {
//                 $s3->deleteObject([
//                     'Bucket' => 'mobilestock-fotos',
//                     'Key' => $nomeimagem
//                 ]);
//             } catch (S3Exception $e) {
//                 return false;
//             }
//             return true;
//         }

//         return false;
//     }

//     /**
//      * Colaborador ignora foto
//      */
//     public function ignoraFoto(int $idProduto, int $idAvaliacao): bool
//     {
//         $this->idProduto = $idProduto;
//         $conexao = Conexao::criarConexao();
//         $denuncia = 1;
//         $query = $conexao->prepare("UPDATE avaliacao_produtos SET ignora_foto = 1 WHERE id_produto = :idProduto AND id = :id");
//         $query->bindParam(':idProduto', $this->idProduto, PDO::PARAM_INT);
//         $query->bindParam(':id', $idAvaliacao, PDO::PARAM_INT);

//         if ($query->execute()) {
//             return true;
//         }
//         return false;
//     }
//     /**
//      * Cliente denuncia foto abusiva
//      */
//     public function denuciaFoto(int $idProduto, int $idAvaliacao): bool
//     {
//         $this->idProduto = $idProduto;
//         $conexao = Conexao::criarConexao();
//         $denuncia = 1;
//         $query = $conexao->prepare("UPDATE avaliacao_produtos SET denuncia_foto = denuncia_foto + :denuncia WHERE id_produto = :idProduto AND id = :id");
//         $query->bindParam(':denuncia', $denuncia, PDO::PARAM_INT);
//         $query->bindParam(':idProduto', $this->idProduto, PDO::PARAM_INT);
//         $query->bindParam(':id', $idAvaliacao, PDO::PARAM_INT);

//         if ($query->execute()) {
//             return true;
//         }
//         return false;
//     }

//     /**
//      * Cliente denuncia comentario abusivo
//      */
//     public function denunciaComentario(int $idProduto, int $idAvaliacao): bool
//     {
//         $this->idProduto = $idProduto;
//         $conexao = Conexao::criarConexao();
//         $denuncia = 1;
//         $query = $conexao->prepare("UPDATE avaliacao_produtos SET denuncia = denuncia + :denuncia WHERE id_produto = :idProduto AND id = :id");
//         $query->bindParam(':denuncia', $denuncia, PDO::PARAM_INT);
//         $query->bindParam(':idProduto', $this->idProduto, PDO::PARAM_INT);
//         $query->bindParam(':id', $idAvaliacao, PDO::PARAM_INT);

//         if ($query->execute()) {
//             return true;
//         }
//         return false;
//     }

//     /**
//      * Busca do banco 3 fotos aleatorias de pares que o cliente comprou e não avaliou,
//      * caso o cliente não possua pares comprados ou para a avaliação o retorno sera uma foto aleatoria de um produto presente no sistema
//      */
//     public function busca3FotosAleatorias(): array
//     {
//         $conexao = Conexao::criarConexao();

//         $query = $conexao->prepare('SELECT 
//                 faturamento_item.id_produto,
//                 produtos.descricao,
//                 produtos_foto.caminho, 
//                 produtos_foto.nome_foto 
            
//             from faturamento_item 
//             INNER JOIN faturamento ON(faturamento.id = faturamento_item.id_faturamento) 
//             INNER JOIN produtos ON(produtos.id = faturamento_item.id_produto) 
//             INNER JOIN produtos_foto on (produtos_foto.id= produtos.id) 
            
//             where faturamento_item.id_cliente =:idCliente 
//             AND faturamento_item.id_produto NOT IN (SELECT ap.id_produto FROM avaliacao_produtos ap WHERE ap.id_cliente = faturamento_item.id_cliente) 
//             AND faturamento.data_emissao>="2020-07-01"
//             AND faturamento.entregue = 1
//             AND faturamento.conferido = 1
//             AND faturamento.separado = 1
//             GROUP BY faturamento_item.id_produto 
//             ORDER BY RAND()LIMIT 3');
//         $query->bindParam(":idCliente", $this->idColaborador, PDO::PARAM_INT);
//         $query->execute();
//         $result = $query->fetchAll(PDO::FETCH_ASSOC);

//         if (count($result) == 0) {
//             $conexao = Conexao::criarConexao();

//             $query = $conexao->prepare('SELECT produtos_foto.caminho, produtos_foto.nome_foto from produtos_foto  ORDER by RAND() LIMIT 1');
//             $query->execute();
//             $result = $query->fetchAll(PDO::FETCH_ASSOC);
//         }

//         return $result;
//     }

//     public function exibeFotosProdutosAvaliados(int $idProduto): string
//     {
//         $conexao = Conexao::criarConexao();
//         $query = $conexao->prepare('SELECT 
//         avaliacao_produtos.id idComentario,
//         avaliacao_produtos.id_produto idProduto,
//         avaliacao_produtos.id_cliente idCliente,
//         avaliacao_produtos.foto_upload url,
//         avaliacao_produtos.denuncia_foto countComplaints,
//         colaboradores.razao_social as name 
//         FROM avaliacao_produtos
        
//         inner join colaboradores on (colaboradores.id = avaliacao_produtos.id_cliente)
//         WHERE avaliacao_produtos.id_produto = :idProduto AND 
//         avaliacao_produtos.foto_upload is not null AND avaliacao_produtos.ignora_foto = 0 AND avaliacao_produtos.foto_upload <> ""
//         ORDER BY data_avaliacao');
//         $query->bindParam(':idProduto', $idProduto, PDO::PARAM_INT);
//         $query->execute();
//         $result = $query->fetchAll(PDO::FETCH_ASSOC);
//         return json_encode($result, true);
//     }

//     /**
//      * lista todos os produtos que o cliente comprou que estao disponiveis para avaliação
//      * @return matriz [ id_produto, descricao, caminho, nome_foto]
//      * */
//     public function retornaAvaliacao(): array
//     {
//         $conexao = Conexao::criarConexao();

//         $query = $conexao->prepare("SELECT 
//                 faturamento_item.id_produto,
//                 produtos.descricao,
//                 produtos_foto.caminho, 
//                 produtos_foto.nome_foto 
                

//                 from faturamento_item 
//                 INNER JOIN faturamento ON(faturamento.id = faturamento_item.id_faturamento) 
//                 INNER JOIN produtos ON(produtos.id = faturamento_item.id_produto) 
//                 INNER JOIN produtos_foto on (produtos_foto.id= produtos.id) 
                
//                 where faturamento_item.id_cliente =:idUsuario 
//                 AND faturamento_item.id_produto NOT IN (SELECT ap.id_produto FROM avaliacao_produtos ap WHERE ap.id_cliente = faturamento.id_cliente) 
//                 AND faturamento.data_emissao>='2020-07-01'
//                 AND faturamento.entregue = 1
//                 AND faturamento.conferido = 1
//                 AND faturamento.separado = 1
//                 GROUP BY faturamento_item.id_produto");


//         $query->bindParam(':idUsuario', $this->idColaborador, PDO::PARAM_INT);
//         $query->execute();

//         return $query->fetchAll(PDO::FETCH_ASSOC);
//     }

//     private function busca5ComentariosProduto(int $pagina): array
//     {
//         $conexao = Conexao::criarConexao();
//         $offSet = $pagina ? $pagina * 5 - 5 : 0;

//         $query = $conexao->prepare('SELECT 
// 			(
// 				CASE
// 					WHEN colaboradores.razao_social <> "" THEN colaboradores.razao_social
// 					ELSE group_concat((SELECT CONCAT
// 					        (UCASE(SUBSTRING(usuarios.nome, 1, 1)),LCASE(SUBSTRING(usuarios.nome, 2)))
// 					        from usuarios where usuarios.id = avaliacao_produtos.id_colaborador), " V.")
// 				END
// 			)razao_social,
// 			avaliacao_produtos.id_colaborador,
// 			avaliacao_produtos.id,
// 			avaliacao_produtos.qualidade,
// 			avaliacao_produtos.custo_beneficio,
// 			avaliacao_produtos.comentario,
// 			avaliacao_produtos.data_avaliacao,
// 			avaliacao_produtos.foto_upload foto_upload
// 			FROM avaliacao_produtos
//             LEFT OUTER JOIN colaboradores on (colaboradores.id=avaliacao_produtos.id_cliente)
//             LEFT OUTER JOIN usuarios on (usuarios.id =colaboradores.id)
//             INNER JOIN produtos ON(avaliacao_produtos.id_produto=produtos.id)
//             WHERE avaliacao_produtos.id_produto=:idProduto
//             AND (
// 					(avaliacao_produtos.comentario IS NOT NULL  AND avaliacao_produtos.comentario <> "" )
//                 OR 
// 					(avaliacao_produtos.foto_upload IS NOT NULL  AND avaliacao_produtos.foto_upload <> "")
// 				)
//                 GROUP BY 
// 					 colaboradores.razao_social,
// 					avaliacao_produtos.id,
// 					avaliacao_produtos.qualidade,
// 					avaliacao_produtos.custo_beneficio,
// 					avaliacao_produtos.comentario,
// 					avaliacao_produtos.data_avaliacao,
// 					avaliacao_produtos.foto_upload
//                 ORDER BY  avaliacao_produtos.foto_upload DESC ,avaliacao_produtos.foto_upload = "", avaliacao_produtos.data_avaliacao DESC LIMIT 5 OFFSET :offSet');

//         $query->bindParam(':idProduto', $this->idProduto, PDO::PARAM_INT);
//         $query->bindParam(':offSet', $offSet, PDO::PARAM_INT);

//         $query->execute();
//         $result = $query->fetchAll(PDO::FETCH_ASSOC);

//         return $result;
//     }
//     /**
//      * metodu usado para buscar itens relacionados a avaliacao de produtos
//      * se nemhum parametro for passado, é retornado em ordem decrescente os itens que possuem denuncias de fotos.
//      * retona 1000 itens de pesquisa
//      * @return json
//      */
//     public function buscaItens(string $descricao = "", int $categoria = 0, int $fornecedor = 0, int $pagina = 0): array
//     {
//         $itens = $this->montaBuscaAvancadaAvaliacao($descricao, $categoria, $fornecedor, $pagina);
//         return $itens;
//     }
//     private function montaBuscaAvancadaAvaliacao(string $descricao, int $categoria, int $fornecedor, int $pagina): array
//     {
//         $offSet = $pagina ? $pagina * 50 - 50 : 0;
//         $desc = "where 1 = 1";
//         if ($descricao) {
//             $desc .= " AND UPPER(produtos.descricao) LIKE UPPER('%$descricao%')";
//         }
//         if ($categoria) {
//             $desc .= " AND produtos.id_categoria = $categoria";
//         }
//         if ($fornecedor) {
//             $desc .= " AND produtos.id_fornecedor = $fornecedor";
//         }
//         $query = 'SELECT avaliacao_produtos.id_produto as id,produtos.descricao,SUM(case WHEN avaliacao_produtos.comentario <> "" AND avaliacao_produtos.comentario IS NOT null then 1 else 0 END) comentarios,
//         COUNT(avaliacao_produtos.id) avaliacoes,
//         SUM(case WHEN avaliacao_produtos.denuncia >= 1 and avaliacao_produtos.comentario is not null then avaliacao_produtos.denuncia else 0 END) denuncia_comentario,
//         SUM(case WHEN avaliacao_produtos.denuncia_foto >= 1 AND avaliacao_produtos.foto_upload like "%https%" then avaliacao_produtos.denuncia_foto  else 0 END) denuncia_foto,  
//         SUM(case WHEN avaliacao_produtos.libera_foto = 0 AND avaliacao_produtos.foto_upload like "%https%" then 1 else 0 END) libera_foto,
//         sum(case WHEN avaliacao_produtos.ignora_foto = 0 AND avaliacao_produtos.foto_upload <> "" and avaliacao_produtos.foto_upload IS NOT NULL THEN 1 ELSE 0 END) countPhoto
//         from avaliacao_produtos INNER JOIN produtos on (avaliacao_produtos.id_produto = produtos.id)';
//         $query .= $desc;
//         $query .= ' GROUP by avaliacao_produtos.id_produto ORDER BY denuncia_foto  DESC,denuncia_comentario  DESC,libera_foto  DESC LIMIT 50 OFFSET ' . $offSet;
//         $conexao = Conexao::criarConexao();
//         $resultado = $conexao->query($query);
//         $lista =  $resultado->fetchAll(PDO::FETCH_ASSOC);
//         return $lista;
//     }
//     private function buscaTodosComentariosProduto(int $pagina = 0): array
//     {
//         $conexao = Conexao::criarConexao();

//         $offSet = $pagina ? $pagina * 50 - 50 : 0;
//         $sql = 'SELECT 
// 			(
// 				CASE
// 					WHEN colaboradores.razao_social <> "" THEN colaboradores.razao_social
// 					ELSE group_concat((SELECT CONCAT
// 					        (UCASE(SUBSTRING(usuarios.nome, 1, 1)),LCASE(SUBSTRING(usuarios.nome, 2)))
// 					        from usuarios where usuarios.id = avaliacao_produtos.id_colaborador), " V.")
// 				END
// 			)razao_social,
// 			avaliacao_produtos.id_colaborador,
// 			avaliacao_produtos.id,
// 			avaliacao_produtos.qualidade,
// 			avaliacao_produtos.custo_beneficio,
// 			avaliacao_produtos.comentario,
// 			avaliacao_produtos.data_avaliacao,
// 			avaliacao_produtos.foto_upload foto_upload
// 			FROM avaliacao_produtos
//             LEFT OUTER JOIN colaboradores on (colaboradores.id=avaliacao_produtos.id_cliente)
//             LEFT OUTER JOIN usuarios on (usuarios.id =colaboradores.id)
//             INNER JOIN produtos ON(avaliacao_produtos.id_produto=produtos.id)
//             WHERE avaliacao_produtos.id_produto=:idProduto
//             AND (
// 					(avaliacao_produtos.comentario IS NOT NULL  AND avaliacao_produtos.comentario <> "" )
//                 OR 
// 					(avaliacao_produtos.foto_upload IS NOT NULL  AND avaliacao_produtos.foto_upload <> "")
// 				)
//                 GROUP BY 
// 					 colaboradores.razao_social,
// 					avaliacao_produtos.id,
// 					avaliacao_produtos.qualidade,
// 					avaliacao_produtos.custo_beneficio,
// 					avaliacao_produtos.comentario,
// 					avaliacao_produtos.data_avaliacao,
// 					avaliacao_produtos.foto_upload
//                 ORDER BY avaliacao_produtos.data_avaliacao DESC, avaliacao_produtos.id_colaborador DESC';
//         if ($pagina) {
//             $sql .= " LIMIT 50 OFFSET $offSet;";
//         }
//         $query = $conexao->prepare($sql);
//         $query->bindParam(':idProduto', $this->idProduto, PDO::PARAM_INT);
//         $query->execute();
//         $result = $query->fetchAll(PDO::FETCH_ASSOC);

//         return $result;
//     }

//     private function verificaClienteAvaliouProduto(int $idProduto): bool
//     {
//         $conexao = Conexao::criarConexao();
//         $query = $conexao->prepare('SELECT * FROM avaliacao_produtos WHERE id_produto = :idProduto and id_cliente = :idCliente;');
//         $query->bindParam(':idCliente', $this->idColaborador, PDO::PARAM_INT);
//         $query->bindParam(':idProduto', $idProduto, PDO::PARAM_INT);
//         $query->execute() or die(print_r($query->errorInfo(), true));
//         $result =  $query->fetchAll(PDO::FETCH_ASSOC);
//         if (count($result) != 0) {
//             return true;
//         } else {
//             return false;
//         }
//     }

//     private function calculaAvaliacaoMedia(): array
//     {
//         $conexao = Conexao::criarConexao();

//         $query = $conexao->prepare('SELECT avaliacao_produtos.id_produto,
//                             SUM(case WHEN avaliacao_produtos.qualidade = 1 then 1 else 0 END) num_1,       
//                             SUM(case WHEN avaliacao_produtos.qualidade = 2 then 1 else 0 END) num_2,
//                             SUM(case WHEN avaliacao_produtos.qualidade = 3 then 1 else 0 END) num_3,
//                             SUM(case WHEN avaliacao_produtos.qualidade = 4 then 1 else 0 END) num_4,
//                             SUM(case WHEN avaliacao_produtos.qualidade = 5 then 1 else 0 END) num_5,
//                             SUM(case WHEN avaliacao_produtos.forma = 1 then 1 else 0 END) tamanho_1,
//                             SUM(case WHEN avaliacao_produtos.forma = 2 then 1 else 0 END) tamanho_2,
//                             SUM(case WHEN avaliacao_produtos.forma = 3 then 1 else 0 END) tamanho_3,
//                             AVG(avaliacao_produtos.qualidade) media_qualidade,
//                             AVG(avaliacao_produtos.custo_beneficio) media_custo_beneficio,
//                             COUNT(*) avaliacoes
//                         from avaliacao_produtos
//                         where avaliacao_produtos.id_produto = :idProduto
//                         GROUP BY avaliacao_produtos.id_produto');
//         $query->bindParam(':idProduto', $this->idProduto, PDO::PARAM_INT);
//         $query->execute();
//         $result = $query->fetchAll(PDO::FETCH_ASSOC);

//         return $result;
//     }

//     private function inserePontos(): bool
//     {
//         $conexao = Conexao::criarConexao();

//         $query = $conexao->prepare("UPDATE colaboradores SET total_pontos = total_pontos+ :pontos WHERE id = :idColaborador");
//         $query->bindParam(':pontos', $this->pontos, PDO::PARAM_INT);
//         $query->bindParam(':idColaborador', $this->idColaborador, PDO::PARAM_INT);

//         return $query->execute() or die(print_r($query->errorInfo(), true));
//     }

//     private function insereHistoricoPontos(int $idProduto): bool
//     {
//         $origem = 5;
//         $operacao = "E";
//         $observacao = "Cliente avaliou produto : $idProduto";

//         $conexao = Conexao::criarConexao();
//         $query = $conexao->prepare("INSERT INTO historico_pontos (id_cliente,quantidade,data_criacao,origem,operacao,observacao) VALUES(:id_cliente,:quantidade,now(),:origem,:operacao,:observacao);");
//         $query->bindParam(':id_cliente', $this->idColaborador, PDO::PARAM_INT);
//         $query->bindParam(':quantidade', $this->pontos, PDO::PARAM_INT);
//         $query->bindParam(':origem', $origem, PDO::PARAM_STR);
//         $query->bindParam(':operacao', $operacao, PDO::PARAM_STR);
//         $query->bindParam(':observacao', $observacao, PDO::PARAM_STR);

//         return $query->execute() or die(print_r($query->errorInfo(), true));
//     }

//     public function copiarAvaliacaoProduto(int $id_produto_origem, int $id_produto_destino): bool
//     {
//         return true;
//     }
// }
