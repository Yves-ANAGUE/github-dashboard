<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\ServiceGithubApi;
use App\Services\ServicePaystack;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardControleur extends Controller
{
    public function __construct(
        private readonly ServiceGithubApi $serviceGithub,
        private readonly ServicePaystack  $servicePaystack,
    ) {}

    public function afficher()
    {
        $utilisateur = Auth::user();

        // Vérification silencieuse : si transaction en_attente → interroger Paystack
        $this->verifierTransactionsEnAttente($utilisateur);

        // Recharger l'utilisateur pour avoir est_donateur à jour
        $utilisateur = $utilisateur->fresh();
        Auth::setUser($utilisateur);

        try {
            $statistiques = $this->serviceGithub->recupererStatistiques($utilisateur);
        } catch (\RuntimeException $exception) {
            $statistiques = [
                'erreur'          => $exception->getMessage(),
                'commits'         => [], 'pull_requests'  => [],
                'issues'          => [], 'quota_restant'  => 0,
                'tous_les_depots' => [], 'nb_depots'      => 0,
                'etoiles_total'   => 0,  'forks_total'    => 0,
                'langages'        => [], 'nb_commits_total' => 0,
                'nb_pull_requests' => 0, 'nb_issues'      => 0,
                'activite_par_jour' => [], 'serie_active'  => 0,
                'nb_followers'    => 0,  'nb_following'   => 0,
                'nb_depots_publics' => 0, 'nb_gists'      => 0,
                'membre_depuis'   => '—', 'biographie'    => null,
                'localisation'    => null, 'site_web'     => null,
                'entreprise'      => null, 'depots_les_plus_stars' => [],
            ];
        }

        return view('dashboard', [
            'utilisateur'  => $utilisateur,
            'statistiques' => $statistiques,
            'estDonateur'  => $utilisateur->estDonateur(),
        ]);
    }

    /** Vérifie silencieusement les transactions MTN en attente au chargement du dashboard */
    private function verifierTransactionsEnAttente($utilisateur): void
    {
        $transactionEnAttente = Transaction::where('utilisateur_id', $utilisateur->id)
                                           ->where('statut', 'en_attente')
                                           ->latest()
                                           ->first();

        if (! $transactionEnAttente) return;

        try {
            $donnees = $this->servicePaystack->verifierTransaction(
                $transactionEnAttente->reference_paystack
            );

            if ($donnees['status'] === 'success') {
                $transactionEnAttente->update([
                    'statut'         => 'succes',
                    'canal_paiement' => $donnees['channel'] ?? null,
                    'metadata'       => json_encode($donnees),
                    'traitee_le'     => now(),
                ]);
                $utilisateur->update(['est_donateur' => true]);
                Log::info("Dashboard : badge attribué automatiquement à {$utilisateur->login}");
            }
        } catch (\Throwable $exception) {
            // Silencieux — ne pas bloquer le chargement du dashboard
            Log::debug("Vérification auto transaction : " . $exception->getMessage());
        }
    }
}