<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createTableIfNotExists('activites_github', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')
                  ->constrained('utilisateurs_github')
                  ->onDelete('cascade');
            $table->string('type_activite');            // 'commits', 'pull_requests', 'issues'
            // JSONB : payload brut GitHub, flexible et indexable
            $table->jsonb('payload');
            $table->integer('annee');
            $table->integer('semaine');                 // Semaine ISO (1-53) pour agrégats
            $table->timestamps();

            // Contrainte unicité : une activité par type/semaine/an par utilisateur
            $table->unique(['utilisateur_id', 'type_activite', 'annee', 'semaine']);
        });

        // Index GIN sur JSONB : O(log n) pour requêtes @> et ? sur le payload
        DB::statement('CREATE INDEX idx_activites_payload ON activites_github USING GIN (payload)');

        // Index expression pour filtrer sur un champ JSONB précis — O(log n)
        DB::statement("CREATE INDEX idx_activites_type ON activites_github (type_activite, annee, semaine)");
    }

    public function down(): void
    {
        Schema::dropIfExists('activites_github');
    }
};