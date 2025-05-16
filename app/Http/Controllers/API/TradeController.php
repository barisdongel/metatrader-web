<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trade;
use App\Models\Position;
use App\Models\Instrument;
use App\Services\TradeService;
use App\Services\PriceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TradeController extends Controller
{
    protected $tradeService;
    protected $priceService;

    public function __construct(TradeService $tradeService, PriceService $priceService)
    {
        $this->tradeService = $tradeService;
        $this->priceService = $priceService;
    }

    /**
     * Yeni bir alım-satım emri oluştur
     */
    public function placeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'instrument_id' => 'required|exists:instruments,id',
            'order_type' => 'required|in:market,limit,stop',
            'direction' => 'required|in:buy,sell',
            'volume' => 'required|numeric|min:0.01',
            'price' => 'required_if:order_type,limit,stop|numeric',
            'stop_loss' => 'nullable|numeric',
            'take_profit' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $data = $request->all();
        $data['user_id'] = $user->id;

        try {
            $result = $this->tradeService->placeOrder($data);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Açık pozisyonu kapat
     */
    public function closePosition(Request $request, $id)
    {
        $user = Auth::user();
        $position = Position::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$position) {
            return response()->json(['error' => 'Pozisyon bulunamadı'], 404);
        }

        try {
            $result = $this->tradeService->closePosition($position);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Pozisyon detaylarını güncelle (Stop Loss, Take Profit)
     */
    public function updatePosition(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'stop_loss' => 'nullable|numeric',
            'take_profit' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $position = Position::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$position) {
            return response()->json(['error' => 'Pozisyon bulunamadı'], 404);
        }

        try {
            $result = $this->tradeService->updatePosition($position, $request->all());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * İşlem geçmişini getir
     */
    public function getHistory(Request $request)
    {
        $user = Auth::user();
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);

        $trades = Trade::where('user_id', $user->id)
            ->with('instrument')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($trades);
    }

    /**
     * Açık pozisyonları getir
     */
    public function getOpenPositions()
    {
        $user = Auth::user();
        $positions = Position::where('user_id', $user->id)
            ->where('status', 'open')
            ->with('instrument')
            ->get();

        // Her pozisyon için anlık kar/zarar hesapla
        foreach ($positions as $position) {
            $instrument = $position->instrument;
            $priceType = $position->direction === 'buy' ? 'bid' : 'ask';
            $currentPrice = $this->priceService->getCurrentPrice($instrument->symbol, $priceType);

            $position->current_profit = $this->tradeService->calculatePositionProfit(
                $position,
                $currentPrice
            );
        }

        return response()->json([
            'positions' => $positions
        ]);
    }

    /**
     * Belirli bir pozisyonun detaylarını getir
     */
    public function getPosition($id)
    {
        $user = Auth::user();
        $position = Position::where('id', $id)
            ->where('user_id', $user->id)
            ->with('instrument')
            ->first();

        if (!$position) {
            return response()->json(['error' => 'Pozisyon bulunamadı'], 404);
        }

        // Anlık kar/zarar hesapla
        $instrument = $position->instrument;
        $priceType = $position->direction === 'buy' ? 'bid' : 'ask';
        $currentPrice = $this->priceService->getCurrentPrice($instrument->symbol, $priceType);

        $position->current_profit = $this->tradeService->calculatePositionProfit(
            $position,
            $currentPrice
        );

        return response()->json([
            'position' => $position
        ]);
    }
}
