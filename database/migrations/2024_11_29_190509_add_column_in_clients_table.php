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
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('status');
            $table->string('email')->nullable()->change();
            $table->string('ddi')->nullable()->change();
            $table->string('whatsapp')->nullable()->change();
            $table->enum('status',['Lead', 'Client','Partner'])->default('Lead')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('client_id');
            $table->string('email')->change();
            $table->string('ddi')->change();
            $table->string('whatsapp')->change();
            $table->enum('status',['Lead', 'Client'])->change();
        });
    }
};
