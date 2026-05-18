<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — {{ $utilisateur->login }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .carte-stat { @apply bg-gray-900 rounded-xl p-5 border border-gray-800 hover:border-gray-600 transition-colors; }
        .badge-lang { @apply px-2 py-1 rounded-md text-xs font-mono font-medium; }
    </style>
</head>
<body class="min-h-screen bg-gray-950 text-white">

{{-- Navbar --}}
<nav class="border-b border-gray-800 bg-gray-900/80 backdrop-blur-sm sticky top-0 z-50 px-6 py-3">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <span class="text-green-400 font-bold text-lg">⚡ GitHub Dashboard</span>
        <div class="flex items-center gap-4">
            @if($estDonateur)
                <span class="bg-yellow-500 text-black text-xs font-bold px-3 py-1 rounded-full animate-pulse">
                    ⭐ Donateur de Test
                </span>
            @endif
            <div class="flex items-center gap-2">
                <img src="{{ $utilisateur->avatar_url }}" class="w-8 h-8 rounded-full border-2 border-green-500">
                <span class="text-gray-300 text-sm font-medium">{{ $utilisateur->login }}</span>
            </div>
            <form method="POST" action="{{ route('auth.deconnexion') }}">
                @csrf
                <button class="text-gray-500 hover:text-red-400 text-sm transition-colors">Déconnexion</button>
            </form>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-6 py-8 space-y-8">

    {{-- Alertes --}}
    @if(session('statut_paiement'))
        <div class="bg-green-900/50 border border-green-500 text-green-300 px-4 py-3 rounded-lg flex items-center gap-2">
            ✅ {{ session('statut_paiement') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-900/50 border border-red-500 text-red-300 px-4 py-3 rounded-lg">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif
    @if(isset($statistiques['erreur']))
        <div class="bg-yellow-900/50 border border-yellow-500 text-yellow-300 px-4 py-3 rounded-lg">
            ⚠️ {{ $statistiques['erreur'] }}
        </div>
    @endif

    {{-- Profil + Don --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Carte profil --}}
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
            <div class="flex items-center gap-4 mb-5">
                <img src="{{ $utilisateur->avatar_url }}" class="w-20 h-20 rounded-full border-2 border-green-500">
                <div>
                    <h2 class="font-bold text-xl">{{ $utilisateur->nom ?? $utilisateur->login }}</h2>
                    <p class="text-gray-400 text-sm">
    <span class="text-gray-600">@</span>{{ $utilisateur->login }}
</p>
                    @if($statistiques['localisation'] ?? null)
                        <p class="text-gray-500 text-xs mt-1">📍 {{ $statistiques['localisation'] }}</p>
                    @endif
                    @if($statistiques['entreprise'] ?? null)
                        <p class="text-gray-500 text-xs">🏢 {{ $statistiques['entreprise'] }}</p>
                    @endif
                </div>
            </div>

            @if($statistiques['biographie'] ?? null)
                <p class="text-gray-400 text-sm mb-4 italic">{{ $statistiques['biographie'] }}</p>
            @endif

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-gray-800 rounded-lg p-3 text-center">
                    <p class="text-xl font-bold text-white">{{ $statistiques['nb_followers'] }}</p>
                    <p class="text-gray-500 text-xs">Followers</p>
                </div>
                <div class="bg-gray-800 rounded-lg p-3 text-center">
                    <p class="text-xl font-bold text-white">{{ $statistiques['nb_following'] }}</p>
                    <p class="text-gray-500 text-xs">Following</p>
                </div>
            </div>

            <div class="space-y-1 text-xs text-gray-500">
                <p>🗓 Membre depuis {{ $statistiques['membre_depuis'] }}</p>
                @if($statistiques['site_web'] ?? null)
                    <p>🔗 <a href="{{ $statistiques['site_web'] }}" target="_blank" class="text-blue-400 hover:underline">{{ $statistiques['site_web'] }}</a></p>
                @endif
                <p class="text-green-400">API Quota restant : {{ $statistiques['quota_restant'] }}</p>
            </div>
        </div>

        {{-- Carte donation --}}
        <div class="lg:col-span-2 bg-gradient-to-br from-purple-900/40 to-blue-900/40 rounded-2xl p-6 border border-purple-700/50">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-bold text-lg mb-1">🧪 Module de Paiement Test</h3>
                    <p class="text-gray-400 text-sm">
                        Tunnel de paiement complet en sandbox Paystack — aucune transaction réelle.
                    </p>
                </div>
                @if($estDonateur)
                    <span class="bg-yellow-500/20 border border-yellow-500 text-yellow-400 text-xs px-3 py-1 rounded-full">
                        ⭐ Badge actif
                    </span>
                @endif
            </div>

            <div class="bg-gray-900/50 rounded-xl p-4 mb-4 text-xs text-gray-400 space-y-2">
    <p class="text-gray-300 font-semibold">🧪 Instructions de test sandbox :</p>
    <div class="bg-gray-800 rounded-lg p-3 space-y-1 font-mono">
        <p class="text-yellow-400 font-semibold">⚠️ Ordre important pour Mobile Money :</p>
        <p>1. Clique sur <span class="text-green-400">Wave</span> → numéro pré-rempli → valide</p>
        <p>2. Ensuite <span class="text-green-400">MTN</span> ou <span class="text-green-400">Orange</span> fonctionnent aussi</p>
    </div>
    <p class="text-gray-500 mt-1">
        Comportement normal du sandbox Paystack XOF — en production les paiements sont indépendants.
    </p>
</div>

            <form method="POST" action="{{ route('don.initier') }}">
                @csrf
                <button type="submit"
                    class="w-full bg-purple-600 hover:bg-purple-500 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center gap-2">
                    💳 Tester le tunnel de paiement
                </button>
            </form>
        </div>
    </div>

    {{-- KPIs principaux --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <div class="carte-stat">
            <p class="text-gray-500 text-xs mb-1">Dépôts</p>
            <p class="text-2xl font-bold text-purple-400">{{ $statistiques['nb_depots'] }}</p>
        </div>
        <div class="carte-stat">
            <p class="text-gray-500 text-xs mb-1">Commits</p>
            <p class="text-2xl font-bold text-green-400">{{ $statistiques['nb_commits_total'] }}</p>
        </div>
        <div class="carte-stat">
            <p class="text-gray-500 text-xs mb-1">Pull Requests</p>
            <p class="text-2xl font-bold text="text-blue-400">{{ $statistiques['nb_pull_requests'] }}</p>
        </div>
        <div class="carte-stat">
            <p class="text-gray-500 text-xs mb-1">Issues Fermées</p>
            <p class="text-2xl font-bold text-orange-400">{{ $statistiques['nb_issues'] }}</p>
        </div>
        <div class="carte-stat">
            <p class="text-gray-500 text-xs mb-1">⭐ Étoiles</p>
            <p class="text-2xl font-bold text-yellow-400">{{ $statistiques['etoiles_total'] }}</p>
        </div>
        <div class="carte-stat">
            <p class="text-gray-500 text-xs mb-1">🍴 Forks</p>
            <p class="text-2xl font-bold text-pink-400">{{ $statistiques['forks_total'] }}</p>
        </div>
        <div class="carte-stat">
            <p class="text-gray-500 text-xs mb-1">🔥 Série</p>
            <p class="text-2xl font-bold text-red-400">{{ $statistiques['serie_active'] }}j</p>
        </div>
    </div>

    {{-- Graphiques --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
            <h3 class="font-bold text-base mb-4">📈 Commits par Semaine</h3>
            <canvas id="graphiqueCommits" height="200"></canvas>
        </div>
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
            <h3 class="font-bold text-base mb-4">📊 Activité par Jour</h3>
            <canvas id="graphiqueJours" height="200"></canvas>
        </div>
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
            <h3 class="font-bold text-base mb-4">🔄 PR vs Issues par Mois</h3>
            <canvas id="graphiquePrIssues" height="200"></canvas>
        </div>
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
            <h3 class="font-bold text-base mb-4">🧑‍💻 Langages Utilisés</h3>
            <canvas id="graphiqueLangages" height="200"></canvas>
        </div>
    </div>

    {{-- Tableau de tous les dépôts avec tri et filtre --}}
    <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <h3 class="font-bold text-lg">📦 Tous les Dépôts ({{ $statistiques['nb_depots'] }})</h3>
            <div class="flex flex-wrap gap-3">
                {{-- Filtre langage --}}
                <select id="filtreLanguage"
                    class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:border-green-500 focus:outline-none">
                    <option value="">Tous les langages</option>
                    @foreach($statistiques['langages'] as $lang => $count)
                        <option value="{{ $lang }}">{{ $lang }} ({{ $count }})</option>
                    @endforeach
                </select>
                {{-- Tri --}}
                <select id="triDepots"
                    class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:border-green-500 focus:outline-none">
                    <option value="updated">Récemment mis à jour</option>
                    <option value="stars">Plus d'étoiles</option>
                    <option value="forks">Plus de forks</option>
                    <option value="name">Nom A→Z</option>
                    <option value="oldest">Plus anciens</option>
                </select>
                {{-- Recherche --}}
                <input type="text" id="rechercheDepot" placeholder="Rechercher..."
                    class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:border-green-500 focus:outline-none w-40">
            </div>
        </div>

        <div id="grilleDepots" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($statistiques['tous_les_depots'] as $depot)
            <div class="depot-carte bg-gray-800 rounded-xl p-4 border border-gray-700 hover:border-gray-500 transition-all duration-200 flex flex-col"
                 data-language="{{ $depot['language'] ?? '' }}"
                 data-stars="{{ $depot['stargazers_count'] }}"
                 data-forks="{{ $depot['forks_count'] }}"
                 data-name="{{ strtolower($depot['name']) }}"
                 data-updated="{{ $depot['updated_at'] }}"
                 data-created="{{ $depot['created_at'] }}">
                <div class="flex items-start justify-between mb-2">
                    <a href="{{ $depot['html_url'] }}" target="_blank"
                       class="text-blue-400 hover:text-blue-300 font-medium text-sm truncate flex-1 mr-2">
                        📁 {{ $depot['name'] }}
                    </a>
                    @if($depot['fork'])
                        <span class="text-gray-600 text-xs border border-gray-700 px-1 rounded">fork</span>
                    @endif
                    @if($depot['private'])
                        <span class="text-yellow-600 text-xs border border-yellow-800 px-1 rounded">privé</span>
                    @endif
                </div>

                <p class="text-gray-500 text-xs mb-3 flex-1 line-clamp-2">
                    {{ $depot['description'] ?? 'Pas de description' }}
                </p>

                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-3">
                        @if($depot['language'])
                            <span class="text-green-400 font-medium">{{ $depot['language'] }}</span>
                        @else
                            <span class="text-gray-600">—</span>
                        @endif
                        <span class="text-yellow-400">⭐ {{ $depot['stargazers_count'] }}</span>
                        <span class="text-gray-500">🍴 {{ $depot['forks_count'] }}</span>
                    </div>
                    <span class="text-gray-600">
                        {{ \Carbon\Carbon::parse($depot['updated_at'])->diffForHumans() }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>

        <p id="aucunResultat" class="hidden text-center text-gray-600 py-8">
            Aucun dépôt ne correspond aux filtres.
        </p>
    </div>

</main>

{{-- Données pour Chart.js --}}
<script>
    window.donneesCommits      = @json($statistiques['commits'] ?? []);
    window.donneesPullRequests = @json($statistiques['pull_requests'] ?? []);
    window.donneesIssues       = @json($statistiques['issues'] ?? []);
    window.donneesJours        = @json($statistiques['activite_par_jour'] ?? []);
    window.donneesLangages     = @json($statistiques['langages'] ?? []);
</script>

</body>
</html>