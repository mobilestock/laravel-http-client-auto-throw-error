<?php

use MobileStock\Shared\PdoInterceptor\Middlewares\CastWithDatabaseColumns;

return ['middlewares' => fn() => [new CastWithDatabaseColumns([])]];
