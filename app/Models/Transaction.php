<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'utilisateur_id',
        'reference_paystack',
        'montant_kobo',
        'devise',
        'statut',
        'canal_paiement',
        'metadata',
        'traitee_le',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'traitee_le'  => 'datetime',
        'montant_kobo' => 'integer',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(UtilisateurGithub::class, 'utilisateur_id');
    }

    /** Montant formaté en dollars */
    public function montantEnDollars(): float
    {
        return $this->montant_kobo / 100;
    }
}