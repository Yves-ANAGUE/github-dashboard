import Chart from 'chart.js/auto';

const PALETTE = [
    '#4ade80', '#60a5fa', '#fb923c', '#f472b6',
    '#a78bfa', '#facc15', '#34d399', '#f87171',
];

const optionsAxes = {
    x: { ticks: { color: '#6b7280' }, grid: { color: '#1f2937' } },
    y: { ticks: { color: '#6b7280' }, grid: { color: '#1f2937' }, beginAtZero: true },
};

const optionsLegende = { labels: { color: '#9ca3af', boxWidth: 12 } };

function creerGraphiqueCommits() {
    const donnees = window.donneesCommits ?? {};
    const ctx = document.getElementById('graphiqueCommits');
    if (!ctx || !Object.keys(donnees).length) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: Object.keys(donnees),
            datasets: [{
                label: 'Commits',
                data: Object.values(donnees),
                borderColor: '#4ade80',
                backgroundColor: 'rgba(74,222,128,0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 2,
            }]
        },
        options: { responsive: true, plugins: { legend: optionsLegende }, scales: optionsAxes }
    });
}

function creerGraphiqueJours() {
    const donnees = window.donneesJours ?? {};
    const ctx = document.getElementById('graphiqueJours');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(donnees),
            datasets: [{
                label: 'Événements',
                data: Object.values(donnees),
                backgroundColor: PALETTE,
                borderRadius: 6,
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: optionsAxes }
    });
}

function agregerParMois(elements) {
    const parMois = {};
    (elements ?? []).forEach(el => {
        if (!el.created_at) return;
        const mois = el.created_at.substring(0, 7);
        parMois[mois] = (parMois[mois] ?? 0) + 1;
    });
    return parMois;
}

function creerGraphiquePrIssues() {
    const prs    = agregerParMois(window.donneesPullRequests);
    const issues = agregerParMois(window.donneesIssues);
    const mois   = [...new Set([...Object.keys(prs), ...Object.keys(issues)])].sort();
    const ctx    = document.getElementById('graphiquePrIssues');
    if (!ctx || !mois.length) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: mois,
            datasets: [
                { label: 'Pull Requests', data: mois.map(m => prs[m] ?? 0), backgroundColor: '#60a5fa', borderRadius: 4 },
                { label: 'Issues',        data: mois.map(m => issues[m] ?? 0), backgroundColor: '#fb923c', borderRadius: 4 },
            ]
        },
        options: { responsive: true, plugins: { legend: optionsLegende }, scales: optionsAxes }
    });
}

function creerGraphiqueLangages() {
    const donnees = window.donneesLangages ?? {};
    const ctx = document.getElementById('graphiqueLangages');
    if (!ctx || !Object.keys(donnees).length) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(donnees),
            datasets: [{
                data: Object.values(donnees),
                backgroundColor: PALETTE,
                borderWidth: 2,
                borderColor: '#111827',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'right', labels: { color: '#9ca3af', boxWidth: 12, padding: 8 } }
            }
        }
    });
}

// ── Tri et filtre des dépôts ────────────────────────────────────────────────

function filtrerEtTrier() {
    const langageChoisi  = document.getElementById('filtreLanguage')?.value ?? '';
    const triChoisi      = document.getElementById('triDepots')?.value ?? 'updated';
    const recherche      = (document.getElementById('rechercheDepot')?.value ?? '').toLowerCase();
    const cartes         = [...document.querySelectorAll('.depot-carte')];

    // Filtrage
    const visibles = cartes.filter(carte => {
        const langageOk  = !langageChoisi || carte.dataset.language === langageChoisi;
        const rechercheOk = !recherche || carte.dataset.name.includes(recherche);
        return langageOk && rechercheOk;
    });

    // Masquer tout
    cartes.forEach(c => c.classList.add('hidden'));

    // Trier les visibles
    visibles.sort((a, b) => {
        switch (triChoisi) {
            case 'stars':   return parseInt(b.dataset.stars)   - parseInt(a.dataset.stars);
            case 'forks':   return parseInt(b.dataset.forks)   - parseInt(a.dataset.forks);
            case 'name':    return a.dataset.name.localeCompare(b.dataset.name);
            case 'oldest':  return new Date(a.dataset.created) - new Date(b.dataset.created);
            default:        return new Date(b.dataset.updated) - new Date(a.dataset.updated);
        }
    });

    // Réordonner dans le DOM et afficher
    const grille = document.getElementById('grilleDepots');
    visibles.forEach(c => { c.classList.remove('hidden'); grille.appendChild(c); });

    document.getElementById('aucunResultat')?.classList.toggle('hidden', visibles.length > 0);
}

document.addEventListener('DOMContentLoaded', () => {
    creerGraphiqueCommits();
    creerGraphiqueJours();
    creerGraphiquePrIssues();
    creerGraphiqueLangages();

    // Écouteurs pour tri/filtre en temps réel
    document.getElementById('filtreLanguage')?.addEventListener('change', filtrerEtTrier);
    document.getElementById('triDepots')?.addEventListener('change', filtrerEtTrier);
    document.getElementById('rechercheDepot')?.addEventListener('input', filtrerEtTrier);

    // Appliquer le tri initial (récemment mis à jour)
    filtrerEtTrier();
});