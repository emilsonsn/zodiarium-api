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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gender');
            $table->text('address');
            $table->integer('day_birth');
            $table->integer('month_birth');
            $table->integer('year_birth');
            $table->integer('hour_birth')->nullable();
            $table->integer('minute_birth')->nullable();    
            $table->string('email')->unique();
            $table->string('ddi');         
            $table->string('whatsapp');         
            $table->enum('status', ['Lead', 'Client'])->default('Lead');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};