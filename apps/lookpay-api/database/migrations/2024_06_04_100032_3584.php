<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('establishment_order_id', 100)->nullable(false)->change();
            $table->dropUnique('invoices_establishment_order_id_unique');
        });
    }
};
