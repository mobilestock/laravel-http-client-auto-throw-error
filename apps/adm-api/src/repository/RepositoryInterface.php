<?php

namespace MobileStock\repository;

use MobileStock\model\ModelInterface;

interface RepositoryInterface
{
    public static function busca($params);

    public static function salva(ModelInterface $model);

    public static function atualiza(ModelInterface $model, $params = []): ModelInterface;

    public static function deleta(ModelInterface $model);
}
