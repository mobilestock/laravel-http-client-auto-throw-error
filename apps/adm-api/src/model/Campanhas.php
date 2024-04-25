<?php

namespace MobileStock\model;

class Campanhas{
    
    private $id;
    private $titulo;
    private $subtitulo;
    private $tags;
    private $uri;

    public function __construct($titulo, $subtitulo, $tags, $uri) {
        $this->titulo = $titulo;
        $this->subtitulo = $subtitulo;
        $this->tags = $tags;
        $this->uri = $uri;
    }

    public function getId():int
    {
        return $this->id;
    }

    public function getTitulo():string
    {
        return $this->titulo;
    }

    public function getSubtitulo():string
    {
        return $this->subtitulo;
    }

    public function getTags():string
    {
        return $this->tags;
    }

    public function getUri():string
    {
        return $this->uri;
    }

    public function setTitulo($titulo):void
    {
        $this->titulo = $titulo;
    }

    public function setSubtitulo($subtitulo):void
    {
        $this->subtitulo = $subtitulo;
    }

    public function setTags($tags):void
    {
        $this->tags = $tags;
    }

    public function setUri($uri):void
    {
        $this->uri = $uri;
    }

}