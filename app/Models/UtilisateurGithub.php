<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtilisateurGithub extends Authenticatable
{
    use Notifiable;

    protected $table      = 'utilisateurs_github';
    protected $primaryKey = 'id';          // ← explicite
    public    $incrementing = true;
    protected $keyType    = 'int';

    protected $fillable = [
        'github_id',
        'login',
        'nom',
        'email',
        'avatar_url',
        'token_acces',
        'est_donateur',
    ];

    protected $hidden = ['token_acces'];

    protected $casts = [
        'est_donateur' => 'boolean',
    ];

    public function activites(): HasMany
    {
        return $this->hasMany(ActiviteGithub::class, 'utilisateur_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'utilisateur_id');
    }

    public function estDonateur(): bool
    {
        return $this->est_donateur;
    }
}