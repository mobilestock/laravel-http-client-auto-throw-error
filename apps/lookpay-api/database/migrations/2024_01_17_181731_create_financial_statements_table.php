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
        Schema::create('financial_statements', function (Blueprint $table) {
            $table->uuidPrimary();
            $table->uuid('establishment_id');
            $table->integer('amount');
            $table->enum('type', array_column(InvoiceItemTypeEnum::cases(), 'value'));
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('is_synced_with_mobilestock')->default(false);
            $table->foreign('establishment_id')->references('id')->on('establishments');
        });
    }
};
