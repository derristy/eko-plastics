<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone');
            $table->text('message')->nullable();
            $table->ipAddress('ip')->nullable();

            $table->string('salesdrive_status')->default('pending');
            $table->string('salesdrive_order_id')->nullable();
            $table->string('dilovod_status')->default('pending');
            $table->string('dilovod_person_id')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index('salesdrive_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
