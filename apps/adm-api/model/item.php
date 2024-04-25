<?php

    class Item
    {

        /**
         * Class Item
         * @package MobileStock\model
         * @property int $id_cliente
         * @property int $id_produto
         * @property int $tamanho
         * @property float $preco
         * @property string $data_hora
         */
        private int $id_cliente;
        private int $id_produto;
        private int $tamanho;
        private float $preco;
        private string $data_hora;

        public function __construct(int $id_cliente, int $id_produto, int $tamanho, float $preco, string $data_hora) {
            $this->id_cliente = $id_cliente;
            $this->id_produto = $id_produto;
            $this->tamanho = $tamanho;
            $this->preco = $preco;
            $this->data_hora = $data_hora;
        }

    }