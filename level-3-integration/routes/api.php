<?php

use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::post('/lead', [LeadController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('lead.store');

Route::post('/salesdrive/webhook', [LeadController::class, 'webhook'])
    ->middleware('throttle:60,1')
    ->name('salesdrive.webhook');

Route::post('/dilovod/webhook', [LeadController::class, 'dilovodWebhook'])
    ->middleware('throttle:60,1')
    ->name('dilovod.webhook');
