<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\ServicePaystack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DonControleur extends Controller
{
    public function __construct(
        private readonly ServicePaystack $servicePaystack
    ) {}

    public function initier()
    {
        $utilisateur = Auth::user();
        $reference   = $this->servicePaystack->genererReference();
        $montantKobo = 100;

        Transaction::create([
            'utilisateur_id'     => $utilisateur->id,
            'reference_paystack' => $reference,
            'montant_kobo'       => $montantKobo,
            'statut'             => 'en_attente',
        ]);

        try {
            $urlPaiement = $this->servicePaystack->initialiserTransaction(
                email: $utilisateur->email ?? $utilisateur->login . '@github.portfolio',
                montantKobo: $montantKobo,
                reference: $reference,
                metadonnees: ['utilisateur_login' => $utilisateur->login]
            );
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['paiement' => $exception->getMessage()]);
        }

        return redirect($urlPaiement);
    }

    public function traiterCallback(Request $request)
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return $this->traiterSansReference();
        }

        $transaction = Transaction::where('reference_paystack', $reference)
                                  ->with('utilisateur')
                                  ->first();

        if (! $transaction) {
            Log::error("Callback : transaction {$reference} introuvable");
            return redirect()->route('accueil');
        }

        if ($transaction->statut === 'succes') {
            Auth::login($transaction->utilisateur);
            return redirect()->route('dashboard.afficher')
                             ->with('statut_paiement', '✅ Paiement déjà confirmé. Badge actif.');
        }

        try {
            $donneesTransaction = $this->servicePaystack->verifierTransaction($reference);
        } catch (\RuntimeException $exception) {
            Log::error("Vérification Paystack échouée : " . $exception->getMessage());
            Auth::login($transaction->utilisateur);
            return redirect()->route('dashboard.afficher')
                             ->withErrors(['paiement' => 'Vérification échouée.']);
        }

        return $this->finaliserTransaction($transaction, $donneesTransaction);
    }

    private function traiterSansReference()
    {
        $utilisateur = Auth::user();

        if (! $utilisateur) {
            return redirect()->route('accueil');
        }

        $transaction = Transaction::where('utilisateur_id', $utilisateur->id)
                                  ->where('statut', 'en_attente')
                                  ->latest()
                                  ->first();

        if (! $transaction) {
            return redirect()->route('dashboard.afficher')
                             ->with('statut_paiement', 'ℹ️ Aucune transaction en attente.');
        }

        try {
            $donneesTransaction = $this->servicePaystack->verifierTransaction(
                $transaction->reference_paystack
            );
        } catch (\RuntimeException $exception) {
            return redirect()->route('dashboard.afficher')
                             ->withErrors(['paiement' => $exception->getMessage()]);
        }

        return $this->finaliserTransaction($transaction, $donneesTransaction);
    }

    private function finaliserTransaction(Transaction $transaction, array $donneesPaystack)
    {
        // Mobile Money encore en attente — pas une erreur
        if ($donneesPaystack['status'] === 'pending') {
            Auth::login($transaction->utilisateur);
            return redirect()->route('dashboard.afficher')
                             ->with('statut_paiement',
                                 '⏳ Paiement en cours. Le badge apparaîtra automatiquement.');
        }

        $statut = $donneesPaystack['status'] === 'success' ? 'succes' : 'echec';

        $transaction->update([
            'statut'         => $statut,
            'canal_paiement' => $donneesPaystack['channel'] ?? null,
            'metadata'       => json_encode($donneesPaystack),
            'traitee_le'     => now(),
        ]);

        $utilisateur = $transaction->utilisateur;

        if ($statut === 'succes') {
            $utilisateur->update(['est_donateur' => true]);
            $utilisateur->refresh();
            Log::info("Badge Donateur attribué à : {$utilisateur->login}");
        }

        Auth::login($utilisateur);

        $messageFlash = $statut === 'succes'
            ? '🎉 Paiement confirmé ! Badge ⭐ Donateur de Test activé.'
            : '❌ Paiement non confirmé.';

        return redirect()->route('dashboard.afficher')
                         ->with('statut_paiement', $messageFlash);
    }
}