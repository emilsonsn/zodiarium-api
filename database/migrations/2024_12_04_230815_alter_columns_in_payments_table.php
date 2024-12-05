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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('identifier');
            $table->string('entity')->nullable()->change();
            $table->enum('origin_api', ['Eupago', 'Stripe'])->after('entity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('identifier')->after('status');
            $table->string('entity')->nullable()->change();
            $table->dropColumn('origin_api');
        });
    }
};
