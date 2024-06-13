<?php

namespace MobileStock\helper\Images\Etiquetas;

class ImagemEtiquetaDevolucao extends ImagemAbstrata
{
    public string $data;
    public string $tipoProblema;

    /**
     * @param string $tipoProblema PRAZO_EXPIRADO_7 | PRAZO_EXPIRADO_60 | SOLICITACAO_PENDENTE
     */
    public function __construct(string $data, string $tipoProblema)
    {
        $this->data = $data;
        $this->tipoProblema = $tipoProblema;
        parent::__construct();
        if ($_ENV['AMBIENTE'] !== 'producao') {
            $this->diretorioFinalDaImagem = $this->diretorioRaiz . '/downloads/etiqueta_devolucao.jpeg';
        }
    }

    public function renderiza()
    {
        $etiqueta = $this->criaImagem();

        switch ($this->tipoProblema) {
            case 'PRAZO_EXPIRADO_7':
                $this->texto($etiqueta, 30, 144, 50, 'DEVOLUÇÃO NÃO ACEITA');
                $this->texto($etiqueta, 16, 80, 110, 'O produto não foi bipado pelo ponto dentro do prazo de 7 dias.');
                $this->texto($etiqueta, 16, 258, 140, 'Data da entrega: ' . $this->data);
                break;
            case 'PRAZO_EXPIRADO_60':
                $this->texto($etiqueta, 30, 168, 50, 'DEVOLUÇÃO EXPIRADA');
                $this->texto($etiqueta, 16, 55, 90, 'Esse produto é de uma compra do meulook que já passou do prazo');
                $this->texto($etiqueta, 16, 165, 115, 'de 60 dias para que o ponto faça a devolução.');
                $this->texto($etiqueta, 16, 243, 140, 'Data da devolução: ' . $this->data);
                break;
            case 'SOLICITACAO_PENDENTE':
                $this->texto($etiqueta, 30, 15, 50, 'TROCA COM SOLICITAÇÃO PENDENTE');
                $this->texto($etiqueta, 16, 75, 110, 'O produto ainda está aguardando a aprovação do fornecedor.');
                break;
        }

        return $etiqueta;
    }
}
