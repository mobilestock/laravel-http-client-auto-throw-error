<?php

use App\Enum\BaseProdutosEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lojas', function (Blueprint $table) {
            $table->integer('id_revendedor')->primary();
            $table->string('nome');
            $table->string('url');
            $table->enum(
                'base_produtos',
                collect(BaseProdutosEnum::cases())
                    ->map(fn (UnitEnum $enum) => $enum->value)
                    ->toArray()
            );
            $table->decimal('percentual_remarcacao');
            $table->defaultTimestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('lojas');
    }
};
