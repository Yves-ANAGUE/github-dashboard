<?php

use App\Http\Controllers\WebhookPaystackControleur;
use Illuminate\Support\Facades\Route;

// Le webhook Paystack est exclu du middleware CSRF (géré par signature HMAC)
Route::post('/webhook/paystack', [WebhookPaystackControleur::class, 'recevoir'])
     ->name('webhook.paystack');