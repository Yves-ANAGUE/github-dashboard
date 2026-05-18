<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ServicePaystack
{
    private string $cleSecrete;
    private string $urlBase;

    public function __construct()
    {
        $this->cleSecrete = config('services.paystack.cle_secrete');
        $this->urlBase    = config('services.paystack.url_base');
    }

    public function initialiserTransaction(
        string $email,
        int $montantKobo,
        string $reference,
        array $metadonnees = []
    ): string {
        $reponse = Http::withToken($this->cleSecrete)
                       ->post("{$this->urlBase}/transaction/initialize", [
                           'email'     => $email,
                           'amount'    => $montantKobo,
                           'reference' => $reference,
                           'currency'  => 'XOF',
                           'metadata'  => $metadonnees,
                           // Aucun 'channels' — exactement comme quand ça marchait
                       ]);

        if (! $reponse->successful() || ! $reponse->json('status')) {
            throw new \RuntimeException(
                'Initialisation Paystack échouée : ' . $reponse->json('message', 'Erreur inconnue')
            );
        }

        return $reponse->json('data.authorization_url');
    }

    public function verifierTransaction(string $reference): array
    {
        $reponse = Http::withToken($this->cleSecrete)
                       ->get("{$this->urlBase}/transaction/verify/{$reference}");

        if (! $reponse->successful()) {
            throw new \RuntimeException(
                'Vérification Paystack échouée : ' . $reponse->json('message', 'Erreur inconnue')
            );
        }

        return $reponse->json('data', []);
    }

    public function genererReference(): string
    {
        return 'dash_' . date('Ymd') . '_' . Str::upper(Str::random(8));
    }

    public function validerSignatureWebhook(string $payloadBrut, string $signatureRecue): bool
    {
        $signatureCalculee = hash_hmac('sha512', $payloadBrut, $this->cleSecrete);
        return hash_equals($signatureCalculee, $signatureRecue);
    }
}