<?php

use App\Http\Controllers\AuthentificationControleur;
use App\Http\Controllers\DashboardControleur;
use App\Http\Controllers\DonControleur;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('accueil'))->name('accueil');

Route::get('/auth/github', [AuthentificationControleur::class, 'redirigerVersGithub'])
     ->name('auth.github');
Route::get('/auth/github/callback', [AuthentificationControleur::class, 'traiterCallbackGithub'])
     ->name('auth.github.callback');
Route::post('/auth/deconnexion', [AuthentificationControleur::class, 'deconnecter'])
     ->name('auth.deconnexion')
     ->middleware('auth');

// ← Hors middleware auth — session peut être perdue après redirect Paystack
Route::get('/don/callback', [DonControleur::class, 'traiterCallback'])
     ->name('don.callback');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardControleur::class, 'afficher'])
         ->name('dashboard.afficher');
    Route::post('/don/initier', [DonControleur::class, 'initier'])
         ->name('don.initier');
});