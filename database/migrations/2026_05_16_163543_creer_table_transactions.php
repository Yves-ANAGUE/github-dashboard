<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createTableIfNotExists('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')
                  ->constrained('utilisateurs_github')
                  ->onDelete('cascade');
            // Clé idempotente — contrainte UNIQUE garantit un seul enregistrement
            $table->string('reference_paystack')->unique();
            $table->integer('montant_kobo');            // En centimes (100 = 1$)
            $table->string('devise', 10)->default('USD');
            $table->string('statut', 30)->default('en_attente');  // en_attente|succes|echec
            $table->string('canal_paiement')->nullable(); // card, mobile_money
            $table->jsonb('metadata')->nullable();       // Données brutes Paystack extras
            $table->timestamp('traitee_le')->nullable(); // Horodatage traitement webhook
            $table->timestamps();
        });

        // Index pour les lookups webhook (recherche par référence) — O(log n)
        DB::statement('CREATE UNIQUE INDEX idx_transactions_reference ON transactions (reference_paystack)');

        // Index partiel : uniquement les transactions réussies (optimise les badges)
        DB::statement("CREATE INDEX idx_transactions_succes ON transactions (utilisateur_id) WHERE statut = 'succes'");
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};