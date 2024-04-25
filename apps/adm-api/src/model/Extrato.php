<?php

namespace MobileStock\model;
use Exception;


class Extrato implements \JsonSerializable
{
    protected $id;
    protected $extrato;

    public function __set($atrib, $value)
    {
        if ($value || $value === "0") {
            $this->$atrib = $value;
        } else {
            $this->$atrib = null;
        }
    }

    public function __get($atrib)
    {
        return $this->$atrib;
    }

   
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
