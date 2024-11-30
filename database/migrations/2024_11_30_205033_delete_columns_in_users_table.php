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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whatsapp');
            $table->dropColumn('cpf_cnpj');
            $table->dropColumn('birth_date');
            $table->dropColumn('photo');
            $table->dropColumn('phone');
            $table->boolean('is_active')->default(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp')->after('email');
            $table->string('cpf_cnpj')->after('whatsapp');
            $table->string('birth_date')->after('cpf_cnpj');
            $table->string('phone')->after('birth_date');
            $table->string('photo')->after('phone');
        });
    }
};
