<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activites_github')) return;

        Schema::create('activites_github', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')
                  ->constrained('utilisateurs_github')
                  ->onDelete('cascade');
            $table->string('type_activite');
            $table->jsonb('payload');
            $table->integer('annee');
            $table->integer('semaine');
            $table->unique(['utilisateur_id', 'type_activite', 'annee', 'semaine']);
            $table->timestamps();
        });

        // Index GIN uniquement sur PostgreSQL — ignoré sur SQLite
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX idx_activites_payload ON activites_github USING GIN (payload)');
            DB::statement('CREATE INDEX idx_activites_type ON activites_github (type_activite, annee, semaine)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activites_github');
    }
};