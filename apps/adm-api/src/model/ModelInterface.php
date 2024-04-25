<?php

namespace MobileStock\model;

interface ModelInterface
{
    /**
     * Cria um objeto a partir da pesquisa do banco de dados
     *
     * @param array $dados
     * @return self
     */
    public static function hidratar(array $dados): self;


    /**
     * Transforma o objeto em um array
     *
     * @return array
     */
    public function extrair(): array;
}
