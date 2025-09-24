<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth
Route::prefix('auth')->group(function () {
    Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);
    Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [\App\Http\Controllers\AuthController::class, 'profile']);
        Route::put('profile', [\App\Http\Controllers\AuthController::class, 'updateProfile']);
        Route::put('password', [\App\Http\Controllers\AuthController::class, 'updatePassword']);
    });
    Route::post('verify-email', [\App\Http\Controllers\AuthController::class, 'verifyEmail']);
    Route::post('resend-verification', [\App\Http\Controllers\AuthController::class, 'resendVerification']);
    Route::post('forgot-password', [\App\Http\Controllers\AuthController::class, 'requestPasswordReset']);
    Route::post('reset-password', [\App\Http\Controllers\AuthController::class, 'resetPassword']);
});

// Affiliate
Route::prefix('affiliate')->middleware('auth:sanctum')->group(function () {
    Route::get('links', [\App\Http\Controllers\AffiliateController::class, 'listLinks']);
    Route::post('links', [\App\Http\Controllers\AffiliateController::class, 'createLink']);
    Route::put('links/{id}/toggle', [\App\Http\Controllers\AffiliateController::class, 'toggleLink']);
    Route::get('stats', [\App\Http\Controllers\AffiliateController::class, 'stats']);
    Route::get('referral-tree', [\App\Http\Controllers\AffiliateController::class, 'referralTree']);
});

// Transactions
Route::prefix('transaction')->group(function () {
    Route::post('', [\App\Http\Controllers\TransactionController::class, 'create']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('', [\App\Http\Controllers\TransactionController::class, 'list']);
    });
});

// Commissions
Route::prefix('commission')->middleware('auth:sanctum')->group(function () {
    Route::get('', [\App\Http\Controllers\CommissionController::class, 'list']);
    Route::get('stats', [\App\Http\Controllers\CommissionController::class, 'stats']);
    Route::put('{id}/status', [\App\Http\Controllers\CommissionController::class, 'updateStatus']);
});

// Coordinator
Route::prefix('coordinator')->middleware(['auth:sanctum','role:coordinator,admin'])->group(function () {
    Route::get('affiliates', [\App\Http\Controllers\CoordinatorController::class, 'listAffiliates']);
    Route::post('affiliates/{affiliateId}/assign', [\App\Http\Controllers\CoordinatorController::class, 'assign']);
    Route::post('affiliates/{affiliateId}/remove', [\App\Http\Controllers\CoordinatorController::class, 'remove']);
    Route::get('stats', [\App\Http\Controllers\CoordinatorController::class, 'stats']);
    Route::get('referrals', [\App\Http\Controllers\CoordinatorController::class, 'referrals']);
});

// Admin
Route::prefix('admin')->middleware(['auth:sanctum','role:admin'])->group(function () {
    Route::get('affiliates', [\App\Http\Controllers\AdminController::class, 'affiliates']);
    Route::get('coordinators', [\App\Http\Controllers\AdminController::class, 'coordinators']);
    Route::get('transactions', [\App\Http\Controllers\AdminController::class, 'transactions']);
    Route::get('commissions/pending', [\App\Http\Controllers\AdminController::class, 'pendingCommissions']);
    Route::put('commissions/{id}/status', [\App\Http\Controllers\AdminController::class, 'updateCommissionStatus']);
});
