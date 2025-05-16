<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instrument;
use App\Models\ChartTemplate;
use App\Services\PriceService;
use App\Services\IndicatorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ChartController extends Controller
{
    protected $priceService;
    protected $indicatorService;

    public function __construct(PriceService $priceService, IndicatorService $indicatorService)
    {
        $this->priceService = $priceService;
        $this->indicatorService = $indicatorService;
    }

    /**
     * Grafik verisini getir (OHLC)
     */
    public function getChartData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|string',
            'timeframe' => 'required|string|in:1m,5m,15m,30m,1h,4h,1d',
            'from' => 'nullable|integer',
            'to' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $symbol = $request->input('symbol');
        $timeframe = $request->input('timeframe', '1h');
        $from = $request->input('from', strtotime('-1 week') * 1000);
        $to = $request->input('to', time() * 1000);

        // Unix timestamp'e dönüştür (milisaniyeden saniyeye)
        $fromTimestamp = intval($from / 1000);
        $toTimestamp = intval($to / 1000);

        $cacheKey = "chart_data_{$symbol}_{$timeframe}_{$fromTimestamp}_{$toTimestamp}";

        // Verileri önbellekten al veya API'den çek
        $chartData = Cache::remember($cacheKey, 60, function () use ($symbol, $timeframe, $fromTimestamp, $toTimestamp) {
            return $this->priceService->getOHLCData($symbol, $timeframe, $fromTimestamp, $toTimestamp);
        });

        return response()->json([
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'data' => $chartData
        ]);
    }

    /**
     * Teknik gösterge hesapla
     */
    public function calculateIndicator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|string',
            'timeframe' => 'required|string|in:1m,5m,15m,30m,1h,4h,1d',
            'indicator' => 'required|string|in:sma,ema,bollinger,macd,rsi',
            'params' => 'nullable|array',
            'from' => 'nullable|integer',
            'to' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $symbol = $request->input('symbol');
        $timeframe = $request->input('timeframe', '1h');
        $indicator = $request->input('indicator');
        $params = $request->input('params', []);
        $from = $request->input('from', strtotime('-1 month') * 1000);
        $to = $request->input('to', time() * 1000);

        // Unix timestamp'e dönüştür
        $fromTimestamp = intval($from / 1000);
        $toTimestamp = intval($to / 1000);

        $cacheKey = "indicator_{$indicator}_{$symbol}_{$timeframe}_{$fromTimestamp}_{$toTimestamp}_" . md5(json_encode($params));

        // Hesaplanmış gösterge verilerini önbellekten al veya hesapla
        $indicatorData = Cache::remember($cacheKey, 60, function () use ($indicator, $symbol, $timeframe, $params, $fromTimestamp, $toTimestamp) {
            // Önce OHLC verilerini al
            $chartData = $this->priceService->getOHLCData($symbol, $timeframe, $fromTimestamp, $toTimestamp);

            // Gösterge hesapla
            return $this->indicatorService->calculate($indicator, $chartData, $params);
        });

        return response()->json([
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'indicator' => $indicator,
            'params' => $params,
            'data' => $indicatorData
        ]);
    }

    /**
     * Kullanıcının kayıtlı şablonlarını getir
     */
    public function getTemplates()
    {
        $user = Auth::user();
        $templates = $user->chartTemplates()->get();

        return response()->json(['templates' => $templates]);
    }

    /**
     * Yeni bir şablon kaydet
     */
    public function saveTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $name = $request->input('name');
        $config = $request->input('config');

        $template = $user->chartTemplates()->create([
            'name' => $name,
            'config' => json_encode($config),
        ]);

        return response()->json(['success' => true, 'template' => $template]);
    }

    /**
     * Şablon sil
     */
    public function deleteTemplate($id)
    {
        $user = Auth::user();
        $template = $user->chartTemplates()->find($id);

        if (!$template) {
            return response()->json(['error' => 'Şablon bulunamadı'], 404);
        }

        $template->delete();

        return response()->json(['success' => true]);
    }
}
