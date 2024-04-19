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
        Schema::create('establishments', function (Blueprint $table) {
            $table->uuidPrimary();
            $table->uuid()->primary();
            $table->char('password', 97);
            $table->char('token', 26)->unique();
            $table->text('iugu_token_live');
            $table->char('phone_number', 11);
            $table->string('name');
            $table->defaultTimestamps();
        });
    }
};
