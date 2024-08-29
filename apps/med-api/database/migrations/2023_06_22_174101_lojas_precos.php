<?php

use App\Enum\TiposRemarcacaoEnum;
use App\Models\Loja;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lojas_precos', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Loja::class, 'id_revendedor');
            $table->decimal('ate')->nullable();
            $table->decimal('remarcacao');
            $table->defaultTimestamps();
        });

        DB::unprepared(
            'INSERT INTO lojas_precos (
                    id_revendedor,
                    remarcacao
                ) SELECT
                    lojas.id_revendedor,
                    lojas.percentual_remarcacao
                    FROM lojas;
                '
        );

        Schema::dropColumns('lojas', ['percentual_remarcacao']);
        Schema::table('lojas', function (Blueprint $table) {
            // depois de 'base_produtos' pra tentar manter um padrão de deixar as datas por último
            $table->enum(
                'tipo_remarcacao',
                collect(TiposRemarcacaoEnum::cases())
                    ->map(fn (UnitEnum $enum) => $enum->value)
                    ->toArray()
            )->after('base_produtos');
        });
    }

    public function down(): void
    {
        Schema::drop('lojas_precos');
    }
};
