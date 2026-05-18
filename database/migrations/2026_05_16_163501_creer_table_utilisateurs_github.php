<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('utilisateurs_github')) return;

        Schema::create('utilisateurs_github', function (Blueprint $table) {
            $table->id();
            $table->string('github_id')->unique();
            $table->string('login')->unique();
            $table->string('nom')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar_url')->nullable();
            $table->text('token_acces');
            $table->boolean('est_donateur')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX idx_utilisateurs_github_id ON utilisateurs_github (github_id)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs_github');
    }
};