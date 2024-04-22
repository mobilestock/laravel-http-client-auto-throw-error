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
        Schema::create('invoices_logs', function (Blueprint $table) {
            $table->uuidPrimary();
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
