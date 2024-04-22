<?php

use App\Enum\Invoice\InvoiceItemTypeEnum;
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
            $table->uuidPrimary();
            $table->uuid('invoice_id');
            $table->enum('type', array_column(InvoiceItemTypeEnum::cases(), 'value'));
            $table->integer('amount');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });
    }
};
