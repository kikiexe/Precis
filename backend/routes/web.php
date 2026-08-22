<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Précis API',
        'version' => '1.0.0',
        'status' => 'operational',
    ]);
});
