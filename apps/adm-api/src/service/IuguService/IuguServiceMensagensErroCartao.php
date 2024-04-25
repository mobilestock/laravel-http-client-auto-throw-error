<?php

namespace MobileStock\service\IuguService;

class IuguServiceMensagensErroCartao
{
    private \PDO $conexao;

    public function __construct(\PDO $conexao)
    {
        $this->conexao = $conexao;
    }

    public function consultaMensagemErro(string $lr): ?string
    {
        $stmt = $this->conexao->prepare(
            "SELECT CONCAT(iugu_mensagens_erro_cartao.mensagem, ' ação recomendada: ', COALESCE(iugu_mensagens_erro_cartao.acao_recomendada, '')) mensagem FROM iugu_mensagens_erro_cartao WHERE iugu_mensagens_erro_cartao.codigo_lr = :lr OR CONCAT('0', iugu_mensagens_erro_cartao.codigo_lr) = :lr LIMIT 1"
        );

        $stmt->execute([
            'lr' => $lr
        ]);

        $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
        return empty($resultado) ? null : $resultado['mensagem'];
    }
}