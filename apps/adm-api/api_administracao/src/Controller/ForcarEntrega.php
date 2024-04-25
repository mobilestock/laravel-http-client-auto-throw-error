<?php

namespace api_administracao\Controller;

use Illuminate\Support\Facades\DB;
use MobileStock\service\EntregaService\EntregaServices;

class ForcarEntrega
{
    public function forcaEntrega(string $uuidProduto)
    {
        DB::beginTransaction();
        EntregaServices::forcarEntregaDeProduto($uuidProduto);
        DB::commit();
    }
}
