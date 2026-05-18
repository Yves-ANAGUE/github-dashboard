<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createTableIfNotExists('utilisateurs_github', function (Blueprint $table) {
            $table->id();
            $table->string('github_id')->unique();
            $table->string('login')->unique();          // Pseudo GitHub
            $table->string('nom')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar_url')->nullable();
            $table->text('token_acces');                // Token OAuth chiffré
            $table->boolean('est_donateur')->default(false);
            $table->timestamps();
        });

        // Index expression sur github_id pour les lookups OAuth
        DB::statement('CREATE INDEX idx_utilisateurs_github_id ON utilisateurs_github (github_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs_github');
    }
};