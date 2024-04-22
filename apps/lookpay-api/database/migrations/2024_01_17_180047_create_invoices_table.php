<?php

use App\Enum\Invoice\PaymentMethodsEnum;
use App\Enum\Invoice\InvoiceStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuidPrimary();
            $table->uuid('establishment_id');
            $table->enum('payment_method', array_column(PaymentMethodsEnum::cases(), 'value'));
            $table->integer('amount');
            $table->integer('fee');
            $table->char('payment_provider_invoice_id', 32)->nullable()->default(null);
            $table->string('establishment_order_id')->unique()->nullable()->default(null);
            $table->enum('status', array_column(InvoiceStatusEnum::cases(), 'value'));
            $table->defaultTimestamps();
            $table->foreign('establishment_id')->references('id')->on('establishments');
        });

        DB::update('ALTER TABLE invoices ADD COLUMN installments TINYINT(2) AFTER payment_method');
    }
};
