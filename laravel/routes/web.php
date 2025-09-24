<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Serve the React SPA for all non-API routes
Route::get('/{any}', function () {
    $path = public_path('app/index.html');
    if (file_exists($path)) {
        return response()->file($path);
    }
    return view('welcome');
})->where('any', '^(?!api).*$');
