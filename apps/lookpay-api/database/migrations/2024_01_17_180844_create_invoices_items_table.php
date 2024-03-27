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
        Schema::create('invoices_items', function (Blueprint $table) {
            $table
                ->uuid('id')
                ->primary()
                ->unique();
            $table->uuid('invoice_id');
            $table->enum('type', ['ADD_CREDIT']);
            $table->float('amount');
            $table->timestamp('created_at')->useCurrent();
            $table
                ->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->noActionOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices_items');
    }
};
