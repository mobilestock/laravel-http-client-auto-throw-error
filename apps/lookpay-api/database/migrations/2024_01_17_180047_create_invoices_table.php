<?php

use App\Enum\Invoice\PaymentMethodsEnum;
use App\Enum\Invoice\StatusEnum;
use App\Helpers\Globals;
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
        $paymentMethodsEnum = Globals::getEnumValues(PaymentMethodsEnum::class);
        $statusEnum = Globals::getEnumValues(StatusEnum::class);
        Schema::create('invoices', function (Blueprint $table) use ($paymentMethodsEnum, $statusEnum) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->enum('payment_method', [$paymentMethodsEnum[0]->value]);
            $table->decimal('amount');
            $table->decimal('fee');
            $table->char('external_id', 32)->nullable()->default(null);
            $table->string('reference_id')->unique()->nullable()->default(null);
            $table->enum('status', [$statusEnum[0]->value]);
            $table->defaultTimestamps();
            $table->foreign('establishment_id')->references('id')->on('establishments');
        });

        DB::update('ALTER TABLE invoices ADD COLUMN installments TINYINT(2) AFTER payment_method');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
