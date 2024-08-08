<?php

use MobileStock\Shared\PdoInterceptor\Middlewares\CastWithDatabaseColumns;

return [
    'middlewares' => fn() => [
        new CastWithDatabaseColumns([
            'tem',
            'eh',
            'pode',
            'falta',
            'esta',
            'ja',
            'possui',
            'permite',
            'deve',
            'existe',
            'afetou',
        ]),
    ],
];
