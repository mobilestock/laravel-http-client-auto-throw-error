<?php

use MobileStock\PdoCast\middlewares\CastWithDatabaseColumns;

return [
    'middlewares' => fn() => [new CastWithDatabaseColumns(['is'])],
];
