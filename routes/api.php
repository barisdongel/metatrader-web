<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\TradeController;
use App\Http\Controllers\API\ChartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Kimlik doğrulama
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Kimlik doğrulaması gerektiren rotalar
Route::middleware('auth:sanctum')->group(function () {
    // Kullanıcı bilgileri
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'updatePassword']);

    // Dashboard ve genel veriler
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/prices', [DashboardController::class, 'getPrices']);
    Route::get('/portfolio-summary', [DashboardController::class, 'getPortfolioSummary']);
    Route::get('/favorite-instruments', [DashboardController::class, 'getFavoriteInstruments']);
    Route::post('/toggle-favorite/{symbol}', [DashboardController::class, 'toggleFavorite']);

    // Grafikler
    Route::get('/charts/data', [ChartController::class, 'getChartData']);
    Route::get('/charts/indicator', [ChartController::class, 'calculateIndicator']);
    Route::get('/charts/templates', [ChartController::class, 'getTemplates']);
    Route::post('/charts/templates', [ChartController::class, 'saveTemplate']);
    Route::delete('/charts/templates/{id}', [ChartController::class, 'deleteTemplate']);

    // İşlemler
    Route::post('/trades/place', [TradeController::class, 'placeOrder']);
    Route::get('/trades/history', [TradeController::class, 'getHistory']);
    Route::get('/trades/positions', [TradeController::class, 'getOpenPositions']);

    // Pozisyonlar
    Route::get('/trades/position/{id}', [TradeController::class, 'getPosition']);
    Route::post('/trades/position/{id}/close', [TradeController::class, 'closePosition']);
    Route::post('/trades/position/{id}/update', [TradeController::class, 'updatePosition']);
});

// Genel API verileri (kimlik doğrulaması gerektirmeyen)
Route::get('/instruments', [DashboardController::class, 'getInstruments']);
Route::get('/market-status', [DashboardController::class, 'getMarketStatus']);
