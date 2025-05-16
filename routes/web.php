<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Ana sayfa
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Kimlik doğrulama rotaları
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Kimlik doğrulaması gerektiren rotalar
Route::middleware('auth')->group(function () {
    // Çıkış
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Gösterge Paneli
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/prices', [DashboardController::class, 'getPrices'])->name('prices');
    Route::get('/portfolio-summary', [DashboardController::class, 'getPortfolioSummary'])->name('portfolio.summary');
    Route::get('/favorite-instruments', [DashboardController::class, 'getFavoriteInstruments'])->name('favorite.instruments');
    Route::post('/toggle-favorite/{symbol}', [DashboardController::class, 'toggleFavorite'])->name('toggle.favorite');

    // Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Grafikler
    Route::get('/charts', [ChartController::class, 'index'])->name('charts.index');
    Route::get('/charts/data', [ChartController::class, 'getChartData'])->name('charts.data');
    Route::get('/charts/indicator', [ChartController::class, 'calculateIndicator'])->name('charts.indicator');
    Route::get('/charts/templates', [ChartController::class, 'getTemplates'])->name('charts.templates');
    Route::post('/charts/templates', [ChartController::class, 'saveTemplate'])->name('charts.templates.save');
    Route::delete('/charts/templates/{id}', [ChartController::class, 'deleteTemplate'])->name('charts.templates.delete');

    // İşlemler
    Route::get('/trades', [TradeController::class, 'index'])->name('trades.index');
    Route::post('/trades/place', [TradeController::class, 'placeOrder'])->name('trades.place');
    Route::get('/trades/history', [TradeController::class, 'getHistory'])->name('trades.history');
    Route::get('/trades/positions', [TradeController::class, 'getOpenPositions'])->name('trades.positions');

    // Pozisyonlar
    Route::get('/trades/position/{id}', [TradeController::class, 'getPosition'])->name('trades.position');
    Route::post('/trades/position/{id}/close', [TradeController::class, 'closePosition'])->name('trades.position.close');
    Route::post('/trades/position/{id}/update', [TradeController::class, 'updatePosition'])->name('trades.position.update');
});
