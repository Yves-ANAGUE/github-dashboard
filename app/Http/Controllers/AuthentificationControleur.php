<?php

namespace App\Http\Controllers;

use App\Models\UtilisateurGithub;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class AuthentificationControleur extends Controller
{
    public function redirigerVersGithub()
    {
        return Socialite::driver('github')
                        ->scopes(['read:user', 'repo'])
                        ->redirect();
    }

    public function traiterCallbackGithub()
    {
        try {
            $utilisateurGithub = Socialite::driver('github')->user();
        } catch (\Throwable $exception) {
            Log::error('OAuth GitHub échoué : ' . $exception->getMessage());
            return redirect('/')->withErrors([
                'oauth' => 'Authentification GitHub échouée. Réessayez.',
            ]);
        }

        $utilisateur = UtilisateurGithub::updateOrCreate(
            ['github_id' => (string) $utilisateurGithub->getId()],
            [
                'login'       => $utilisateurGithub->getNickname(),
                'nom'         => $utilisateurGithub->getName(),
                'email'       => $utilisateurGithub->getEmail(),
                'avatar_url'  => $utilisateurGithub->getAvatar(),
                'token_acces' => $utilisateurGithub->token,
            ]
        );

        // Log de diagnostic — à retirer en production
        Log::info('Utilisateur trouvé/créé', [
            'id'    => $utilisateur->id,
            'login' => $utilisateur->login,
        ]);

        Auth::login($utilisateur, remember: true);

        // Vérification immédiate
        if (! Auth::check()) {
            Log::error('Auth::login() a échoué pour : ' . $utilisateur->login);
            return redirect('/')->withErrors([
                'oauth' => 'Impossible de créer la session. Vérifiez config/auth.php.',
            ]);
        }

        return redirect()->route('dashboard.afficher');
    }

    public function deconnecter()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }
}