<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')) return;

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')
                  ->constrained('utilisateurs_github')
                  ->onDelete('cascade');
            $table->string('reference_paystack')->unique();
            $table->integer('montant_kobo');
            $table->string('devise', 10)->default('USD');
            $table->string('statut', 30)->default('en_attente');
            $table->string('canal_paiement')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('traitee_le')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX idx_transactions_reference ON transactions (reference_paystack)');
            DB::statement("CREATE INDEX idx_transactions_succes ON transactions (utilisateur_id) WHERE statut = 'succes'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};