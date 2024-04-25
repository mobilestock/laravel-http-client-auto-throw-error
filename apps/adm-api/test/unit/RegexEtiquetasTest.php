<?php

use MobileStock\model\Entrega;
use MobileStock\model\EntregasEtiqueta;
use MobileStock\model\LogisticaItemModel;
use Ramsey\Uuid\Rfc4122\UuidV4;

class RegexEtiquetasTest extends test\TestCase
{
    public function dadosForcarEntregaEntregue()
    {
        $uuid = UuidV4::uuid4();
        return [
            'Etiqueta de produto' => [
                LogisticaItemModel::REGEX_ETIQUETA_PRODUTO,
                [
                    ['1234_afb3u438erh384237e42423ed.12323', true],
                    ['f1234_afb3u438erh384237e42423ed.fssdf12323', false],
                    ['1234_afb3u438erh384237e42423ed.', false],
                    ['1234_afb3u438erh384237e42423ed', false],
                    ['_afb3u438erh384237e42423ed.12323', false],
                    ['afb3u438erh384237e42423ed.12323', false],
                    ['342323_.12323', false],
                    ['12323', false],
                    ['ifnsuhr233', false],
                    ['', false],
                ],
            ],
            'Etiqueta de cliente' => [
                Entrega::REGEX_ETIQUETA_CLIENTE,
                [
                    ['C123', true],
                    ['P123', false],
                    [123, false],
                    [$uuid . '_123_TROCA', false],
                    [$uuid . '_123_ENTREGA', false],
                    ['', false],
                ],
            ],
            'Etiqueta de cliente legado' => [
                Entrega::REGEX_ETIQUETA_CLIENTE_LEGADO,
                [
                    [$uuid . '_123_TROCA', true],
                    [$uuid . '_123_ENTREGA', true],
                    ['C123', false],
                    ['P123', false],
                    [123, false],
                    ['', false],
                ],
            ],

            'Etiqueta de volume' => [
                EntregasEtiqueta::REGEX_VOLUME,
                [['123_456_7', true], [$uuid . '_' . $uuid, false], [$uuid . '_' . $uuid . '_123', false], ['', false]],
            ],
            'Etiqueta de volume legado' => [
                EntregasEtiqueta::REGEX_VOLUME_LEGADO,
                [[$uuid . '_' . $uuid, true], [$uuid . '_' . $uuid . '123', false], ['', false]],
            ],
        ];
    }
    /**
     * @dataProvider dadosForcarEntregaEntregue
     */
    public function testVerificaRegexEtiquetas($regex, $etiquetas)
    {
        foreach ($etiquetas as $etiqueta) {
            $this->assertEquals(preg_match($regex, $etiqueta[0]), $etiqueta[1]);
        }
    }
}
