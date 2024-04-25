<?php

namespace MobileStock\service;

use MobileStock\helper\GeradorSql;
use MobileStock\helper\GradeImagens;
use MobileStock\model\MensagensNovidades;
use PDO;

class MensagensNovidadesService extends MensagensNovidades
{

    public function salva(PDO $conexao): void
    {
        $gerador = new GeradorSql($this);
        $sql = $gerador->insert();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
    }

    public function atualiza(PDO $conexao): bool
    {
        $gerador = new GeradorSql($this);
        $sql = $gerador->updateSomenteDadosPreenchidos();
        $stmt = $conexao->prepare($sql);
        $stmt->execute($gerador->bind);
        return true;
    }

    public function enviaNotificacao(PDO $conexao): void
    {
        $sql = $conexao->prepare(
            "SELECT
                CONCAT('[',
                    GROUP_CONCAT(JSON_OBJECT(
                            'texto', mensagens_novidades.json_texto,
                            'id', mensagens_novidades.id
                        ) LIMIT 4),
                ']') AS `mensagens`
            FROM mensagens_novidades
            WHERE mensagens_novidades.situacao = 'PE'
            GROUP BY mensagens_novidades.categoria
            ORDER BY mensagens_novidades.id ASC
            LIMIT 1"
        );
        $sql->execute();
        $mensagens = $sql->fetchColumn();
        if(empty($mensagens)) return;
        $mensagens = json_decode($mensagens, true);

        $sql = $conexao->prepare(
            "SELECT 
                colaboradores.telefone
            FROM colaboradores
            WHERE colaboradores.inscrito_receber_novidades"
        );
        $sql->execute();
        $data = $sql->fetchAll(PDO::FETCH_ASSOC);

        $mensagemFinal = "";
        foreach ($mensagens as $index => $mensagem) {
            $mensagensJson = json_decode($mensagem['texto'], true);
            $mensagemFinal .= $mensagensJson['texto'];
            $imagens[$index] = $mensagensJson['foto'];

            $this->id = $mensagem["id"];
            $this->situacao = "EV";
            $this->atualiza($conexao);
        }
        $msgService = new MessageService();
        $imagemUnica = $this->criaUnicaImagemParaVariasFotos($imagens);
        foreach ($data as $colaborador) {
            if(sizeof($mensagens) <= 1)  {
                $msgService->sendImageWhatsApp($colaborador['telefone'], $imagens[0], $mensagemFinal);
                sleep(2);
                continue;
            }
            $msgService->sendImageBase64WhatsApp($colaborador['telefone'], $imagemUnica, $mensagemFinal);
            sleep(2);
        }
    }

    private function criaUnicaImagemParaVariasFotos(array $imagens): ?string
    {
        $grade = new GradeImagens(800, 800, 10, 10);
        if (sizeof($imagens) > 1 && sizeof($imagens) <= 3) {
            $posX = 0;
            $posY = 0;
            foreach ($imagens as $index => $imagem) {
                if ($index == 1) {
                    $posY = 5;
                }
                if ($index == 2) {
                    $posX = 5;
                }
                $img = imagecreatefromjpeg($imagem);
                $grade->adicionarImagem($img, 5, 5, $posX, $posY);
                imagedestroy($img);
            }
            return $grade->renderizar();
        }
        $posX = 0;
        $posY = 0;
        foreach ($imagens as $index => $imagem) {
            if ($index == 1) {
                $posY = 5;
            }
            if ($index == 2) {
                $posX = 5;
            }
            if ($index == 3) {
                $posY = 0;
            }
            $img = imagecreatefromjpeg($imagem);
            $grade->adicionarImagem($img, 5, 5, $posX, $posY);
            imagedestroy($img);
        }
        return $grade->renderizar();
    }
}