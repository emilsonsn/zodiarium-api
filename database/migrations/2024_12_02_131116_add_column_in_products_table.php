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
        Schema::table('products', function (Blueprint $table) {
            $table->string('reports_array')->after('is_active')->change();
            $table->renameColumn('reports_array', 'report');
            $table->enum('type', ['Main', 'Bundle', 'Upsell'])->after('is_active')->default('Upsell');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->json('report')->after('is_active')->change();
            $table->renameColumn('report', 'reports_array');
        });
    }
};
