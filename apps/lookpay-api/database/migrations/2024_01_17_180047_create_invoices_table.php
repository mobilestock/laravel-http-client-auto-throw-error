<?php

use App\Enum\Invoice\PaymentMethodsEnum;
use App\Enum\Invoice\StatusEnum;
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
        Schema::create('invoices2', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->uuid('establishment_id');
            $table->enum('payment_method', PaymentMethodsEnum::returnPaymentMethods());
            $table->decimal('amount');
            $table->decimal('fee');
            $table->char('external_id', 32)->nullable()->default(null);
            $table->string('reference_id', 255)->unique()->nullable()->default(null);
            $table->enum('status', StatusEnum::returnStatus())->default('CREATED');
            $table->defaultTimestamps();
            $table->foreign('establishment_id')->references('id')->on('establishments');
        });

        DB::update('ALTER TABLE invoices2 ADD COLUMN installments TINYINT(2) AFTER payment_method');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
