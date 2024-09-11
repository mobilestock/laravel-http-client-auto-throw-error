<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lojas', function (Blueprint $tabela) {
            $tabela->char('telefone', 11)->nullable()->after('id_revendedor');
        });

        $bancoDados = env('DB_DATABASE_MOBILE_STOCK');
        DB::update(
            "UPDATE lojas
            INNER JOIN $bancoDados.colaboradores ON $bancoDados.colaboradores.id = lojas.id_revendedor
            SET lojas.telefone = $bancoDados.colaboradores.telefone
            WHERE TRUE;"
        );
    }
};
