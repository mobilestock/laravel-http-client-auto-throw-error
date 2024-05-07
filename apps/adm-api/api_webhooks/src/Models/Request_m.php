<?php
namespace api_webhooks\Models;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated
 */
class Request_m{
    protected $request;
    protected $resposta;
    protected $json;

    public function __construct()
    {
       $this->request = Request::createFromGlobals();
       $this->resposta = new Response('ok','200');
       $this->json = $this->request->getContent();
    }
}
?>
