<?php

use App\Enum\Invoice\ItemTypeEnum;
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
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->enum('type', ItemTypeEnum::returnItemTypes());
            $table->decimal('amount');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
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
