<?php


namespace MobileStock\helper;


class AsyncRequester
{
    private $url;
    private $params;

    public function __construct(string $url, array $params = [])
    {
        $this->url = $url;
        $this->params = $params;
    }

    public function post()
    {
        $parts = parse_url($this->url);

        $fp = fsockopen($parts['host'],
            isset($parts['port'])?$parts['port']:80,
            $errno, $errstr, 30);

        $out = "POST ".$parts['path']." HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-Type: application/json\r\n";
        $out.= "Content-Length: 0\r\n";
        $out.= "Connection: Close\r\n\r\n";

        fwrite($fp, $out);
        fclose($fp);
    }

    public function get()
    {
        $parts = parse_url($this->url);

        $fp = fsockopen($parts['host'],
            isset($parts['port'])?$parts['port']:80,
            $errno, $errstr, 30);
        
        if (isset($parts['query'])) {
            $parts['query'] = '?' . $parts['query'];
        } else {
            $parts['query'] = '';
        }

        $out = "GET ". $parts['path'] . $parts['query'] . " HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-type: application/x-www-form-urlencoded\r\n";
        $out.= "Content-Length: 0\r\n";
        $out.= "Connection: Close\r\n\r\n";

        fwrite($fp, $out);
        fclose($fp);
    }

}