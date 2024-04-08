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
        Schema::create('establishments_webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id');
            $table->string('url', 255);
            $table->string('secret_token', 255)->nullable();
            $table->enum('event_type', ['INVOICE_EXPIRED']);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['establishment_id', 'event_type']);
            $table->foreign('establishment_id')->references('id')->on('establishments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establishments_webhooks');
    }
};
