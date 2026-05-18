<?php

namespace App\Services;

use App\Models\ActiviteGithub;
use App\Models\UtilisateurGithub;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class ServiceGithubApi
{
    private string $urlBase = 'https://api.github.com';
    private int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = (int) env('GITHUB_CACHE_TTL', 900);
    }

    private function entetesPourRequete(string $token): array
    {
        return [
            'Authorization'        => "Bearer {$token}",
            'Accept'               => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }

    public function recupererStatistiques(UtilisateurGithub $utilisateur): array
    {
        $login = $utilisateur->login;
        $token = $utilisateur->token_acces;

        // Profil enrichi GitHub
        $profil = Cache::remember("gh_profil_{$login}", $this->cacheTtl, fn() =>
            $this->appeler("{$this->urlBase}/users/{$login}", $token)
        );

        // Tous les dépôts (pagination automatique)
        $tousLesDepots = Cache::remember("gh_depots_{$login}", $this->cacheTtl, fn() =>
            $this->recupererTousLesDepots($login, $token)
        );

        // Événements publics (commits)
        $evenements = Cache::remember("gh_events_{$login}", $this->cacheTtl, fn() =>
            $this->appeler("{$this->urlBase}/users/{$login}/events/public?per_page=100", $token)
        );

        // Pull Requests
        $pullRequests = Cache::remember("gh_prs_{$login}", $this->cacheTtl, fn() =>
            $this->rechercherElements("author:{$login}+type:pr", $token)
        );

        // Issues fermées
        $issuesFermees = Cache::remember("gh_issues_{$login}", $this->cacheTtl, fn() =>
            $this->rechercherElements("author:{$login}+type:issue+is:closed", $token)
        );

        $this->persisterActivites($utilisateur, $evenements, $pullRequests, $issuesFermees);

        return [
            // Métriques profil
            'nb_followers'        => $profil['followers'] ?? 0,
            'nb_following'        => $profil['following'] ?? 0,
            'nb_depots_publics'   => $profil['public_repos'] ?? 0,
            'nb_gists'            => $profil['public_gists'] ?? 0,
            'membre_depuis'       => isset($profil['created_at'])
                                     ? (new \DateTime($profil['created_at']))->format('Y')
                                     : '—',
            'biographie'          => $profil['bio'] ?? null,
            'localisation'        => $profil['location'] ?? null,
            'site_web'            => $profil['blog'] ?? null,
            'entreprise'          => $profil['company'] ?? null,

            // Métriques dépôts
            'tous_les_depots'     => $tousLesDepots,
            'nb_depots'           => count($tousLesDepots),
            'etoiles_total'       => array_sum(array_column($tousLesDepots, 'stargazers_count')),
            'forks_total'         => array_sum(array_column($tousLesDepots, 'forks_count')),
            'langages'            => $this->extraireLanguages($tousLesDepots),
            'depots_les_plus_stars' => $this->trierParEtoiles($tousLesDepots, 5),

            // Métriques activité
            'commits'             => $this->agregerParSemaine($evenements, 'PushEvent'),
            'nb_commits_total'    => $this->compterCommitsTotal($evenements),
            'pull_requests'       => $pullRequests,
            'nb_pull_requests'    => count($pullRequests),
            'issues'              => $issuesFermees,
            'nb_issues'           => count($issuesFermees),
            'activite_par_jour'   => $this->agregerParJourSemaine($evenements),
            'serie_active'        => $this->calculerSerieActive($evenements),

            'quota_restant'       => $this->verifierQuotaRestant($token),
        ];
    }

    /** Récupère TOUS les dépôts avec pagination (max 10 pages = 1000 dépôts) */
    private function recupererTousLesDepots(string $login, string $token): array
    {
        $tousLesDepots = [];
        $page = 1;

        do {
            $page_depots = $this->appeler(
                "{$this->urlBase}/users/{$login}/repos?sort=updated&per_page=100&page={$page}",
                $token
            );

            if (empty($page_depots) || ! is_array($page_depots)) break;

            $tousLesDepots = array_merge($tousLesDepots, $page_depots);
            $page++;

        } while (count($page_depots) === 100 && $page <= 10);

        return $tousLesDepots;
    }

    private function rechercherElements(string $requete, string $token): array
    {
        $reponse = $this->appeler(
            "{$this->urlBase}/search/issues?q={$requete}&per_page=100",
            $token
        );
        return $reponse['items'] ?? [];
    }

    private function appeler(string $url, string $token): array
    {
        try {
            $reponse = Http::withHeaders($this->entetesPourRequete($token))
                           ->timeout(15)
                           ->get($url);

            if ($reponse->status() === 401) {
                throw new \RuntimeException('Token GitHub révoqué ou expiré.');
            }

            $quotaRestant = (int) ($reponse->header('X-RateLimit-Remaining') ?? 999);
            if ($quotaRestant < 10) {
                throw new \RuntimeException("Quota GitHub épuisé : {$quotaRestant} restantes.");
            }

            $reponse->throw();
            return $reponse->json() ?? [];

        } catch (RequestException $exception) {
            throw new \RuntimeException(
                "Erreur GitHub [{$exception->response->status()}] : " . $exception->getMessage()
            );
        }
    }

    private function verifierQuotaRestant(string $token): int
    {
        try {
            $reponse = Http::withHeaders($this->entetesPourRequete($token))
                           ->get("{$this->urlBase}/rate_limit");
            return $reponse->json('rate.remaining', 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    /** Agrège commits par semaine ISO pour Chart.js */
    private function agregerParSemaine(array $evenements, string $type): array
    {
        $parSemaine = [];
        foreach ($evenements as $evt) {
            if (($evt['type'] ?? '') !== $type) continue;
            $semaine = (new \DateTime($evt['created_at']))->format('Y-W');
            $parSemaine[$semaine] = ($parSemaine[$semaine] ?? 0)
                + count($evt['payload']['commits'] ?? []);
        }
        ksort($parSemaine);
        return $parSemaine;
    }

    /** Agrège activité par jour de la semaine (0=Dim … 6=Sam) */
    private function agregerParJourSemaine(array $evenements): array
    {
        $jours = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        $compteur = array_fill(0, 7, 0);

        foreach ($evenements as $evt) {
            $jour = (int) (new \DateTime($evt['created_at']))->format('w');
            $compteur[$jour]++;
        }

        $resultat = [];
        foreach ($compteur as $index => $compte) {
            $resultat[$jours[$index]] = $compte;
        }
        return $resultat;
    }

    private function compterCommitsTotal(array $evenements): int
    {
        $total = 0;
        foreach ($evenements as $evt) {
            if (($evt['type'] ?? '') === 'PushEvent') {
                $total += count($evt['payload']['commits'] ?? []);
            }
        }
        return $total;
    }

    /** Calcule la série de jours consécutifs avec activité */
    private function calculerSerieActive(array $evenements): int
    {
        if (empty($evenements)) return 0;

        $jours = [];
        foreach ($evenements as $evt) {
            $jours[] = (new \DateTime($evt['created_at']))->format('Y-m-d');
        }
        $jours = array_unique($jours);
        rsort($jours);

        $serie = 1;
        $hier = new \DateTime($jours[0]);

        for ($i = 1; $i < count($jours); $i++) {
            $jour = new \DateTime($jours[$i]);
            $diff = $hier->diff($jour)->days;
            if ($diff === 1) { $serie++; $hier = $jour; }
            else break;
        }

        return $serie;
    }

    /** Extrait la distribution des langages depuis les dépôts */
    private function extraireLanguages(array $depots): array
    {
        $langages = [];
        foreach ($depots as $depot) {
            $lang = $depot['language'] ?? null;
            if ($lang) {
                $langages[$lang] = ($langages[$lang] ?? 0) + 1;
            }
        }
        arsort($langages);
        return array_slice($langages, 0, 8, preserve_keys: true);
    }

    /** Trie les dépôts par étoiles décroissantes */
    private function trierParEtoiles(array $depots, int $limite): array
    {
        usort($depots, fn($a, $b) => $b['stargazers_count'] <=> $a['stargazers_count']);
        return array_slice($depots, 0, $limite);
    }

    private function persisterActivites(
        UtilisateurGithub $utilisateur,
        array $evenements,
        array $pullRequests,
        array $issues
    ): void {
        $semaine = (int) date('W');
        $annee   = (int) date('Y');

        foreach ([
            'commits'       => $evenements,
            'pull_requests' => $pullRequests,
            'issues'        => $issues,
        ] as $type => $payload) {
            ActiviteGithub::updateOrCreate(
                [
                    'utilisateur_id' => $utilisateur->id,
                    'type_activite'  => $type,
                    'annee'          => $annee,
                    'semaine'        => $semaine,
                ],
                ['payload' => json_encode($payload)]
            );
        }
    }
}