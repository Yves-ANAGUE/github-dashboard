<?php

namespace App\Http\Controllers;

use App\Jobs\TraiterPaiementWebhook;
use App\Services\ServicePaystack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookPaystackControleur extends Controller
{
    public function __construct(
        private readonly ServicePaystack $servicePaystack
    ) {}

    /**
     * Point d'entrée du webhook Paystack.
     * Doit retourner HTTP 200 en < 5 secondes.
     * Le traitement lourd est délégué à un Job asynchrone.
     */
    public function recevoir(Request $request)
    {
        $payloadBrut     = $request->getContent();
        $signatureRecue  = $request->header('x-paystack-signature', '');

        // Vérification HMAC immédiate — rejette sans log si invalide
        if (! $this->servicePaystack->validerSignatureWebhook($payloadBrut, $signatureRecue)) {
            Log::warning('Webhook Paystack : signature invalide', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Signature invalide'], 401);
        }

        $payload = json_decode($payloadBrut, associative: true);

        // Seul l'événement 'charge.success' nous intéresse
        if (($payload['event'] ?? '') !== 'charge.success') {
            return response()->json(['message' => 'Événement ignoré'], 200);
        }

        // Dispatch asynchrone pour ne pas bloquer la réponse
        TraiterPaiementWebhook::dispatch($payload);

        return response()->json(['message' => 'Webhook reçu'], 200);
    }
}