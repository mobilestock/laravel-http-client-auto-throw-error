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
        Schema::create('financial_statements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->decimal('amount');
            $table->enum('type', array_column(ItemTypeEnum::cases(), 'value'));
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('is_synced')->default(false);
            $table->foreign('establishment_id')->references('id')->on('establishments');
        });
    }
};
