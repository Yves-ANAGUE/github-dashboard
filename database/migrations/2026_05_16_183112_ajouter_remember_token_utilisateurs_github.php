<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utilisateurs_github', function (Blueprint $table) {
            $table->rememberToken(); // Ajoute la colonne remember_token nullable
        });
    }

    public function down(): void
    {
        Schema::table('utilisateurs_github', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};