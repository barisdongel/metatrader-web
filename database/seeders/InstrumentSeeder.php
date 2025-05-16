<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Instrument;
use App\Models\User;

class InstrumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Forex Enstrümanları
        $forexInstruments = [
            [
                'name' => 'Euro / US Dollar',
                'symbol' => 'EURUSD',
                'type' => 'forex',
                'digits' => 5,
                'point' => 0.00001,
                'pip_value' => 10.00,
                'contract_size' => 100000.00,
                'margin_required' => 3.33,
                'description' => 'Euro versus US Dollar',
                'currency' => 'EUR',
                'quote_currency' => 'USD',
                'trading_hours' => json_encode([
                    'monday' => [['start' => '00:00', 'end' => '24:00']],
                    'tuesday' => [['start' => '00:00', 'end' => '24:00']],
                    'wednesday' => [['start' => '00:00', 'end' => '24:00']],
                    'thursday' => [['start' => '00:00', 'end' => '24:00']],
                    'friday' => [['start' => '00:00', 'end' => '23:59']],
                ]),
                'min_lot' => 0.01,
                'max_lot' => 100.00,
                'lot_step' => 0.01,
                'swap_long' => -7.00,
                'swap_short' => 0.50,
                'is_popular' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Great Britain Pound / US Dollar',
                'symbol' => 'GBPUSD',
                'type' => 'forex',
                'digits' => 5,
                'point' => 0.00001,
                'pip_value' => 10.00,
                'contract_size' => 100000.00,
                'margin_required' => 3.33,
                'description' => 'British Pound versus US Dollar',
                'currency' => 'GBP',
                'quote_currency' => 'USD',
                'trading_hours' => json_encode([
                    'monday' => [['start' => '00:00', 'end' => '24:00']],
                    'tuesday' => [['start' => '00:00', 'end' => '24:00']],
                    'wednesday' => [['start' => '00:00', 'end' => '24:00']],
                    'thursday' => [['start' => '00:00', 'end' => '24:00']],
                    'friday' => [['start' => '00:00', 'end' => '23:59']],
                ]),
                'min_lot' => 0.01,
                'max_lot' => 100.00,
                'lot_step' => 0.01,
                'swap_long' => -7.50,
                'swap_short' => 0.70,
                'is_popular' => true,
                'is_active' => true,
            ],
            [
                'name' => 'US Dollar / Japanese Yen',
                'symbol' => 'USDJPY',
                'type' => 'forex',
                'digits' => 3,
                'point' => 0.001,
                'pip_value' => 10.00,
                'contract_size' => 100000.00,
                'margin_required' => 3.33,
                'description' => 'US Dollar versus Japanese Yen',
                'currency' => 'USD',
                'quote_currency' => 'JPY',
                'trading_hours' => json_encode([
                    'monday' => [['start' => '00:00', 'end' => '24:00']],
                    'tuesday' => [['start' => '00:00', 'end' => '24:00']],
                    'wednesday' => [['start' => '00:00', 'end' => '24:00']],
                    'thursday' => [['start' => '00:00', 'end' => '24:00']],
                    'friday' => [['start' => '00:00', 'end' => '23:59']],
                ]),
                'min_lot' => 0.01,
                'max_lot' => 100.00,
                'lot_step' => 0.01,
                'swap_long' => -8.50,
                'swap_short' => 0.30,
                'is_popular' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Australian Dollar / US Dollar',
                'symbol' => 'AUDUSD',
                'type' => 'forex',
                'digits' => 5,
                'point' => 0.00001,
                'pip_value' => 10.00,
                'contract_size' => 100000.00,
                'margin_required' => 3.33,
                'description' => 'Australian Dollar versus US Dollar',
                'currency' => 'AUD',
                'quote_currency' => 'USD',
                'trading_hours' => json_encode([
                    'monday' => [['start' => '00:00', 'end' => '24:00']],
                    'tuesday' => [['start' => '00:00', 'end' => '24:00']],
                    'wednesday' => [['start' => '00:00', 'end' => '24:00']],
                    'thursday' => [['start' => '00:00', 'end' => '24:00']],
                    'friday' => [['start' => '00:00', 'end' => '23:59']],
                ]),
                'min_lot' => 0.01,
                'max_lot' => 100.00,
                'lot_step' => 0.01,
                'swap_long' => -5.70,
                'swap_short' => 0.20,
                'is_popular' => true,
                'is_active' => true,
            ],
        ];

        // Kripto Enstrümanları
        $cryptoInstruments = [
            [
                'name' => 'Bitcoin / US Dollar',
                'symbol' => 'BTCUSD',
                'type' => 'crypto',
                'digits' => 2,
                'point' => 0.01,
                'pip_value' => 0.10,
                'contract_size' => 1.00,
                'margin_required' => 50.00,
                'description' => 'Bitcoin versus US Dollar',
                'currency' => 'BTC',
                'quote_currency' => 'USD',
                'trading_hours' => json_encode([
                    'monday' => [['start' => '00:00', 'end' => '24:00']],
                    'tuesday' => [['start' => '00:00', 'end' => '24:00']],
                    'wednesday' => [['start' => '00:00', 'end' => '24:00']],
                    'thursday' => [['start' => '00:00', 'end' => '24:00']],
                    'friday' => [['start' => '00:00', 'end' => '24:00']],
                    'saturday' => [['start' => '00:00', 'end' => '24:00']],
                    'sunday' => [['start' => '00:00', 'end' => '24:00']],
                ]),
                'min_lot' => 0.01,
                'max_lot' => 10.00,
                'lot_step' => 0.01,
                'swap_long' => -15.00,
                'swap_short' => -15.00,
                'is_popular' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Ethereum / US Dollar',
                'symbol' => 'ETHUSD',
                'type' => 'crypto',
                'digits' => 2,
                'point' => 0.01,
                'pip_value' => 0.10,
                'contract_size' => 1.00,
                'margin_required' => 50.00,
                'description' => 'Ethereum versus US Dollar',
                'currency' => 'ETH',
                'quote_currency' => 'USD',
                'trading_hours' => json_encode([
                    'monday' => [['start' => '00:00', 'end' => '24:00']],
                    'tuesday' => [['start' => '00:00', 'end' => '24:00']],
                    'wednesday' => [['start' => '00:00', 'end' => '24:00']],
                    'thursday' => [['start' => '00:00', 'end' => '24:00']],
                    'friday' => [['start' => '00:00', 'end' => '24:00']],
                    'saturday' => [['start' => '00:00', 'end' => '24:00']],
                    'sunday' => [['start' => '00:00', 'end' => '24:00']],
                ]),
                'min_lot' => 0.01,
                'max_lot' => 100.00,
                'lot_step' => 0.01,
                'swap_long' => -15.00,
                'swap_short' => -15.00,
                'is_popular' => true,
                'is_active' => true,
            ],
        ];

        // Emtia Enstrümanları
        $commodityInstruments = [
            [
                'name' => 'Gold / US Dollar',
                'symbol' => 'XAUUSD',
                'type' => 'commodities',
                'digits' => 2,
                'point' => 0.01,
                'pip_value' => 0.10,
                'contract_size' => 100.00,
                'margin_required' => 5.00,
                'description' => 'Gold versus US Dollar',
                'currency' => 'XAU',
                'quote_currency' => 'USD',
                'trading_hours' => json_encode([
                    'monday' => [['start' => '00:00', 'end' => '24:00']],
                    'tuesday' => [['start' => '00:00', 'end' => '24:00']],
                    'wednesday' => [['start' => '00:00', 'end' => '24:00']],
                    'thursday' => [['start' => '00:00', 'end' => '24:00']],
                    'friday' => [['start' => '00:00', 'end' => '23:59']],
                ]),
                'min_lot' => 0.01,
                'max_lot' => 50.00,
                'lot_step' => 0.01,
                'swap_long' => -9.50,
                'swap_short' => -8.50,
                'is_popular' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Silver / US Dollar',
                'symbol' => 'XAGUSD',
                'type' => 'commodities',
                'digits' => 3,
                'point' => 0.001,
                'pip_value' => 1.00,
                'contract_size' => 5000.00,
                'margin_required' => 5.00,
                'description' => 'Silver versus US Dollar',
                'currency' => 'XAG',
                'quote_currency' => 'USD',
                'trading_hours' => json_encode([
                    'monday' => [['start' => '00:00', 'end' => '24:00']],
                    'tuesday' => [['start' => '00:00', 'end' => '24:00']],
                    'wednesday' => [['start' => '00:00', 'end' => '24:00']],
                    'thursday' => [['start' => '00:00', 'end' => '24:00']],
                    'friday' => [['start' => '00:00', 'end' => '23:59']],
                ]),
                'min_lot' => 0.01,
                'max_lot' => 100.00,
                'lot_step' => 0.01,
                'swap_long' => -5.00,
                'swap_short' => -5.00,
                'is_popular' => false,
                'is_active' => true,
            ],
        ];

        // Tüm enstrümanları birleştir
        $allInstruments = array_merge($forexInstruments, $cryptoInstruments, $commodityInstruments);

        // Enstrümanları veritabanına ekle
        foreach ($allInstruments as $instrument) {
            Instrument::create($instrument);
        }
    }
}
