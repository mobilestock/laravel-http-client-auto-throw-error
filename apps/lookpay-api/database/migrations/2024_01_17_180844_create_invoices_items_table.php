<?php

use App\Enum\Invoice\ItemTypeEnum;
use App\Helpers\Globals;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $itemTypeEnum = Globals::getEnumValues(ItemTypeEnum::class);
        Schema::create('invoices_items', function (Blueprint $table) use ($itemTypeEnum) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->enum('type', [$itemTypeEnum[0]->value]);
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
