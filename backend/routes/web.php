<?php

use Illuminate\Support\Facades\Route;

// The Vue app (built into public/app by `npm run build` in ../frontend) owns every
// page; Laravel only answers /api, /sanctum and /up. Serving it from the same origin
// is what lets Sanctum authenticate with its session cookie.
Route::get('/{path?}', function () {
    $index = public_path('app/index.html');
    abort_unless(is_file($index), 503, 'Frontend not built. Run `npm run build` in frontend/.');

    return response()->file($index, ['Cache-Control' => 'no-cache']);
})->where('path', '^(?!api/|sanctum/|up$).*');
