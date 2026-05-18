<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\UtilisateurGithub;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TraiterPaiementWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;          // 3 tentatives max en cas d'échec
    public int $backoff = 60;       // 60 secondes entre les tentatives

    public function __construct(
        private readonly array $payload
    ) {}

    /**
     * Traitement idempotent : si la transaction existe déjà avec statut 'succes',
     * on ne fait rien. La contrainte UNIQUE sur reference_paystack est le filet de sécurité SQL.
     */
    public function handle(): void
    {
        $reference       = $this->payload['data']['reference'] ?? null;
        $loginUtilisateur = $this->payload['data']['metadata']['utilisateur_login'] ?? null;

        if (! $reference) {
            Log::error('Webhook Paystack : référence manquante dans le payload', $this->payload);
            return;
        }

        DB::transaction(function () use ($reference, $loginUtilisateur) {
            // Verrou SELECT FOR UPDATE — empêche les traitements concurrents du même webhook
            $transaction = Transaction::lockForUpdate()
                                      ->where('reference_paystack', $reference)
                                      ->first();

            if (! $transaction) {
                Log::warning("Webhook : transaction {$reference} introuvable en BDD");
                return;
            }

            // Idempotence : si déjà traitée avec succès, on s'arrête
            if ($transaction->statut === 'succes') {
                Log::info("Webhook : transaction {$reference} déjà traitée — ignorée");
                return;
            }

            $transaction->update([
                'statut'         => 'succes',
                'canal_paiement' => $this->payload['data']['channel'] ?? null,
                'metadata'       => json_encode($this->payload['data']),
                'traitee_le'     => now(),
            ]);

            // Attribution du badge donateur
            if ($loginUtilisateur) {
                UtilisateurGithub::where('login', $loginUtilisateur)
                                  ->update(['est_donateur' => true]);
            }

            Log::info("Webhook Paystack traité avec succès : {$reference}");
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job TraiterPaiementWebhook échoué', [
            'reference' => $this->payload['data']['reference'] ?? 'inconnue',
            'erreur'    => $exception->getMessage(),
        ]);
    }
}