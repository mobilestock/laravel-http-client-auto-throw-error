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
        $typeEnum = Globals::getEnumValues(ItemTypeEnum::class);
        Schema::create('financial_statements', function (Blueprint $table) use ($typeEnum) {
            $table->uuid('id')->primary();
            $table->uuid('for');
            $table->decimal('amount');
            $table->enum('type', [$typeEnum[0]->value]);
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('is_pending')->default(false);
            $table->foreign('for')->references('id')->on('establishments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_statements');
    }
};
