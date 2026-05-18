<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiviteGithub extends Model
{
    protected $table = 'activites_github';

    protected $fillable = [
        'utilisateur_id',
        'type_activite',
        'payload',
        'annee',
        'semaine',
    ];

    protected $casts = [
        // Eloquent sérialise/désérialise automatiquement le JSONB
        'payload' => 'array',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(UtilisateurGithub::class, 'utilisateur_id');
    }
}