<?php

namespace MobileStock\helper\Images\ImplementacaoImagemGD;

use Intervention\Image\Image;

class EtiquetaDevolucaoGD extends ImagemGDAbstrata
{
    public string $data;
    public string $tipoProblema;

    /**
     * @param string $tipoProblema PRAZO_EXPIRADO_7 | PRAZO_EXPIRADO_60 | SOLICITACAO_PENDENTE
     */
    public function __construct(
        string $data,
        string $tipoProblema,
        int $larguraDaImagem = 800,
        int $alturaDaImagem = 170)
    {
        $this->data = $data;
        $this->tipoProblema = $tipoProblema;
        parent::__construct($larguraDaImagem, $alturaDaImagem);

        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem = "{$this->diretorioRaiz}/downloads/etiqueta_devolucao.jpeg";
        }
    }

    public function renderizar(): Image
    {
        $etiqueta = parent::criarImagem();

        switch ($this->tipoProblema) {
            case 'PRAZO_EXPIRADO_7':
                parent::aplicarTexto($etiqueta, 40, 144, 20, 'DEVOLUÇÃO NÃO ACEITA');
                parent::aplicarTexto($etiqueta, 22, 73, 90, 'O produto não foi bipado pelo ponto dentro do prazo de 7 dias.');
                parent::aplicarTexto($etiqueta, 22, 250, 130, 'Data da entrega: ' . $this->data);
                break;
            case 'PRAZO_EXPIRADO_60':
                parent::aplicarTexto($etiqueta, 40, 155, 20, 'DEVOLUÇÃO EXPIRADA');
                parent::aplicarTexto($etiqueta, 22, 40, 80, 'Esse produto é de uma compra do meulook que já passou do prazo');
                parent::aplicarTexto($etiqueta, 22, 165, 105, 'de 60 dias para que o ponto faça a devolução.');
                parent::aplicarTexto($etiqueta, 22, 243, 130, 'Data da devolução: ' . $this->data);
                break;
            case 'SOLICITACAO_PENDENTE':
                parent::aplicarTexto($etiqueta, 40, 15, 30, 'TROCA COM SOLICITAÇÃO PENDENTE');
                parent::aplicarTexto($etiqueta, 22, 75, 100, 'O produto ainda está aguardando a aprovação do fornecedor.');
                break;
        }

        return $etiqueta;
    }
}
