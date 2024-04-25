<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/traits/HttpClientTestTrait.php';

class HttpClientTest extends TestCase
{
    use HttpClientTestTrait;

    public function testPostMethod()
    {
        $this->httpClient->post(
            $_ENV['URL_MOBILE'] . 'teste-api.php', // Servidor de teste que retorna tudo que for enviado
            [
                'waifu' => 'Kyou Kai',
                'husbando' => 'Thorffin'
            ],
            [
                'joaquim: 123'
            ]
        );
    }

    public function testGetMethod()
    {
        $this->httpClient->get(
             $_ENV['URL_MOBILE'] . 'teste-api.php?teste=teste&bait=fraco&futebol=true',
            [],
            [
                'joaquim: 123'
            ]
        );
    }

    public function testPutMethod()
    {
        $this->httpClient->put(
            $_ENV['URL_MOBILE'] . 'teste-api.php?bait=fraco',
            [
                'bait' => 'forte'
            ],
            [
                'parametro: headers'
            ]
        );
    }

    public function testPatchMethod()
    {
        $this->httpClient->patch(
            $_ENV['URL_MOBILE'] . 'teste-api.php?teste=patcheste',
            [
                'messi' => 'goat',
                'bait' => 'nao e bait'
            ],
            [
                'peteca' => 'ruim'
            ]
        );
    }

    public function testDeleteMethod()
    {
        $this->httpClient->delete(
            $_ENV['URL_MOBILE'] . 'teste-api.php?teste=patcheste',
            [
                'cr7' => 'goat',
                'bait' => 'fortissimo'
            ],
            [
                'parametro: headers'
            ]
        );
    }

    public function testPassingArrayOnBody()
    {
        $this->httpClient->get(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                [
                    'teste' => 'teste',
                    'teste2' => 'teste2'
                ]
            ],
            []
        );
        
        $this->httpClient->post(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                [
                    'teste' => 'teste',
                    'teste2' => 'teste2'
                ]
            ],
            []
        );

        $this->httpClient->put(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                [
                    'teste' => 'teste',
                    'teste2' => 'teste2'
                ]
            ],
            []
        );

        $this->httpClient->delete(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                [
                    'teste' => 'teste',
                    'teste2' => 'teste2'
                ]
            ],
            []
        );

        $this->httpClient->patch(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                [
                    'teste' => 'teste',
                    'teste2' => 'teste2'
                ]
            ],
            []
        );
    }

    public function testPassingStdClassOnBody()
    {
        $object = new stdClass();
        $object->name = 'stdClass';
        $object->extension = 'extension';
        $this->httpClient->post(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                $object
            ],
            []
        );

        $this->httpClient->get(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                $object
            ],
            []
        );

        $this->httpClient->delete(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                $object
            ],
            []
        );

        $this->httpClient->put(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                $object
            ],
            []
        );

        $this->httpClient->patch(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            [
                $object
            ],
            []
        );
    }

    public function testPassingXmlStringOnBody()
    {
        $xmlString = <<<XML
        <?xml version='1.0'?>
            <document>
            <title>Forty What?</title>
            <from>Joe</from>
            <to>Jane</to>
            <body>
            I know that's the answer -- but what's the question?
            </body>
            </document>
        XML;

        $this->httpClient->post(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            $xmlString,
            [] 
        );

        $this->httpClient->get(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            $xmlString,
            [] 
        );

        $this->httpClient->put(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            $xmlString,
            [] 
        );

        $this->httpClient->delete(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            $xmlString,
            [] 
        );

        $this->httpClient->patch(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            $xmlString,
            []
        );
    }

    public function testPassingClassWhoImplementsJsonSerializable()
    {
        $this->httpClient->post(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            new class implements JsonSerializable{
                public function jsonSerialize()
                {
                    return [
                        'name' => 'JsonSerializable',
                        'extension' => 'extension'
                    ];
                }
            },
            []
        );

        $this->httpClient->get(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            new class implements JsonSerializable{
                public function jsonSerialize()
                {
                    return [
                        'name' => 'JsonSerializable',
                        'extension' => 'extension'
                    ];
                }
            },
            []
        );

        $this->httpClient->put(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            new class implements JsonSerializable{
                public function jsonSerialize()
                {
                    return [
                        'name' => 'JsonSerializable',
                        'extension' => 'extension'
                    ];
                }
            },
            []
        );

        $this->httpClient->delete(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            new class implements JsonSerializable{
                public function jsonSerialize()
                {
                    return [
                        'name' => 'JsonSerializable',
                        'extension' => 'extension'
                    ];
                }
            },
            []
        );

        $this->httpClient->patch(
            $_ENV['URL_MOBILE'] . 'teste-api.php',
            new class implements JsonSerializable{
                public function jsonSerialize()
                {
                    return [
                        'name' => 'JsonSerializable',
                        'extension' => 'extension'
                    ];
                }
            },
            []
        );
    }
}