<?php

namespace MobileStock\service;

use Illuminate\Support\Facades\DB;
use MobileStock\helper\GeradorSql;
use MobileStock\helper\Images\ImplementacaoImagemGD\ImagensEmGradeGD;
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

    public function enviaNotificacao(): void
    {
        $sql = "SELECT
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
            LIMIT 1";

        $mensagens = DB::selectColumns($sql);
        if (empty($mensagens)) {
            return;
        }

        $sql = "SELECT
                colaboradores.telefone
            FROM colaboradores
            WHERE colaboradores.inscrito_receber_novidades";
        $data = DB::selectColumns($sql);

        $mensagemFinal = '';
        foreach ($mensagens as $index => $mensagem) {
            $mensagensJson = json_decode($mensagem['texto'], true);
            $mensagemFinal .= $mensagensJson['texto'];
            $imagens[$index] = $mensagensJson['foto'];

            $this->id = $mensagem['id'];
            $this->situacao = 'EV';
            $this->atualiza(DB::getPdo());
        }
        $msgService = new MessageService();
        $imagensEmGrade = new ImagensEmGradeGD($imagens);
        $imagemUnica = $imagensEmGrade->gerarGradeDeImagensEmBase64();

        foreach ($data as $colaborador) {
            if (sizeof($mensagens) <= 1) {
                $msgService->sendImageWhatsApp($colaborador['telefone'], $imagens[0], $mensagemFinal);
                continue;
            }
            $msgService->sendImageBase64WhatsApp($colaborador['telefone'], $imagemUnica, $mensagemFinal);
        }
    }
}
