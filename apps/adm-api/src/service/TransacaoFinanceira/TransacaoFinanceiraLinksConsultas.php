<?php

namespace MobileStock\service\TransacaoFinanceira;

use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransacaoFinanceiraLinksConsultas
{
    private PDO $conexao;

    public function __construct(PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    public function buscaInformacoesLink(string $idMd5): array
    {
        $stmt = $this->conexao->prepare('SELECT 
            transacao_financeiras_links.id,
            (SELECT transacao_financeiras.status FROM transacao_financeiras WHERE transacao_financeiras_links.id_transacao = transacao_financeiras.id) situacao,
            (SELECT usuarios.token FROM usuarios WHERE id_colaborador = transacao_financeiras_links.id_cliente LIMIT 1)token,
            (SELECT JSON_OBJECT(
                "id", colaboradores.id,
                "nome", colaboradores.razao_social,
                "foto_perfil", colaboradores.foto_perfil
            ) FROM colaboradores WHERE colaboradores.id = transacao_financeiras_links.id_cliente) cliente,
            transacao_financeiras_links.valor,
            (SELECT configuracoes.segundos_expirar_expirar_link_pagamento FROM configuracoes) - TIMESTAMPDIFF(SECOND, transacao_financeiras_links.criado_em, CURRENT_TIMESTAMP()) tempo_restante_em_segundos
        FROM transacao_financeiras_links WHERE MD5(transacao_financeiras_links.id) = ?');

        $stmt->execute([$idMd5]);

        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($consulta)) {
            throw new NotFoundHttpException('Link não encontrado');
        }

        $consulta['cliente'] = json_decode($consulta['cliente'], true);
        $consulta['valor'] = (float)$consulta['valor'];
        $consulta['token'] = $consulta['token'];
        return $consulta;
    }
}