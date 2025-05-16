<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Trade;
use App\Models\Position;
use App\Models\Instrument;
use App\Services\PriceService;
use App\Services\TradeService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $priceService;
    protected $tradeService;

    public function __construct(PriceService $priceService, TradeService $tradeService)
    {
        $this->priceService = $priceService;
        $this->tradeService = $tradeService;
    }

    /**
     * Dashboard verilerini getir
     */
    public function index()
    {
        $user = Auth::user();
        $positions = Position::where('user_id', $user->id)
            ->with('instrument')
            ->get();

        $recentTrades = Trade::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->with('instrument')
            ->get();

        $accountBalance = $user->account_balance;
        $equity = $this->tradeService->calculateEquity($user->id);
        $popularInstruments = Instrument::where('is_popular', true)->get();

        // Hesap özeti bilgilerini al
        $accountSummary = [
            'balance' => $accountBalance,
            'equity' => $equity,
            'margin' => $this->tradeService->calculateMargin($user->id),
            'free_margin' => $equity - $this->tradeService->calculateMargin($user->id),
            'margin_level' => $this->tradeService->calculateMarginLevel($user->id),
            'profit_today' => $this->tradeService->calculateProfitToday($user->id),
        ];

        return response()->json([
            'account_summary' => $accountSummary,
            'positions' => $positions,
            'recent_trades' => $recentTrades,
            'popular_instruments' => $popularInstruments,
        ]);
    }

    /**
     * Fiyat akışı verisi için kullanılan yöntem
     */
    public function getPrices(Request $request)
    {
        $instruments = $request->input('instruments', []);

        return response()->json([
            'prices' => $this->priceService->getCurrentPrices($instruments),
            'timestamp' => now()->timestamp,
        ]);
    }

    /**
     * Kullanıcı portföy özetini döndürür
     */
    public function getPortfolioSummary()
    {
        $user = Auth::user();
        $portfolioData = $this->tradeService->getPortfolioSummary($user->id);

        return response()->json($portfolioData);
    }

    /**
     * Popüler/favori enstrümanları döndürür
     */
    public function getFavoriteInstruments()
    {
        $user = Auth::user();
        $favorites = $user->favoriteInstruments()->get();

        return response()->json([
            'favorites' => $favorites,
        ]);
    }

    /**
     * Favori olarak ekle/kaldır
     */
    public function toggleFavorite($symbol)
    {
        $user = Auth::user();
        $instrument = Instrument::where('symbol', $symbol)->firstOrFail();

        $isFavorite = $user->favoriteInstruments()->where('instruments.id', $instrument->id)->exists();

        if ($isFavorite) {
            $user->favoriteInstruments()->detach($instrument->id);
            $message = 'Enstrüman favorilerden kaldırıldı.';
        } else {
            $user->favoriteInstruments()->attach($instrument->id);
            $message = 'Enstrüman favorilere eklendi.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_favorite' => !$isFavorite,
        ]);
    }

    /**
     * Tüm enstrümanları getirir
     */
    public function getInstruments()
    {
        $instruments = Instrument::where('is_active', true)->get();

        return response()->json([
            'instruments' => $instruments,
        ]);
    }

    /**
     * Piyasa durumunu getirir
     */
    public function getMarketStatus()
    {
        $status = [
            'forex' => true,
            'crypto' => true,
            'stocks' => false,
            'indices' => false,
            'commodities' => true,
        ];

        $currentHour = now()->hour;

        // Örnek: Forex piyasası hafta içi 24 saat açık
        if (now()->isWeekend()) {
            $status['forex'] = false;
        }

        // Örnek: Borsa saatleri (9:30-16:00 gibi)
        if ($currentHour < 9 || $currentHour >= 16 || now()->isWeekend()) {
            $status['stocks'] = false;
        }

        return response()->json([
            'status' => $status,
            'server_time' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
        ]);
    }
}
