<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'contact');

Route::get('/health', fn() => response()->json(['ok' => true, 'time' => now()->toIso8601String()]));
