<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Note: Short URL download route moved to api.php as /api/t/{token}
// This prevents the React SPA from intercepting the route
