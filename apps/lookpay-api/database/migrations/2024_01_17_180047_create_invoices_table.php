<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table
                ->uuid('id')
                ->primary()
                ->unique();
            $table->uuid('establishment_id');
            $table->enum('payment_method', ['CREDIT_CARD']);
            $table->tinyInteger('installments')->default(1);
            $table->float('amount');
            $table->float('fee');
            $table
                ->char('external_id', 26)
                ->unique()
                ->nullable()
                ->default(null);
            $table
                ->char('reference_id', 26)
                ->unique()
                ->nullable()
                ->default(null);
            $table->enum('status', ['CREATED', 'PENDING', 'PAID', 'REFUNDED', 'EXPIRED'])->default('CREATED');
            $table->defaultTimestamps();
            $table
                ->foreign('establishment_id')
                ->references('id')
                ->on('establishments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
