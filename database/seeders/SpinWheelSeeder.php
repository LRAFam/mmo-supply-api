<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SpinWheel;
use App\Models\WheelPrize;

class SpinWheelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Daily Free Wheel
        $dailyWheel = SpinWheel::create([
            'name' => 'Daily Spin',
            'type' => 'free',
            'cost' => 0,
            'cooldown_hours' => 24,
            'is_active' => true,
        ]);

        // Daily wheel prizes - small rewards for free daily spin
        $dailyPrizes = [
            ['name' => 'Better Luck!', 'type' => 'nothing', 'value' => 0, 'weight' => 35, 'color' => '#6b7280', 'icon' => '😢'],
            ['name' => '$0.10 Credit', 'type' => 'wallet_credit', 'value' => 0.10, 'weight' => 30, 'color' => '#3b82f6', 'icon' => '💵'],
            ['name' => '$0.25 Credit', 'type' => 'wallet_credit', 'value' => 0.25, 'weight' => 20, 'color' => '#10b981', 'icon' => '💵'],
            ['name' => '$0.50 Credit', 'type' => 'wallet_credit', 'value' => 0.50, 'weight' => 10, 'color' => '#8b5cf6', 'icon' => '💰'],
            ['name' => '$1.00 Credit', 'type' => 'wallet_credit', 'value' => 1.00, 'weight' => 4, 'color' => '#f59e0b', 'icon' => '💰'],
            ['name' => '$5.00 JACKPOT!', 'type' => 'wallet_credit', 'value' => 5.00, 'weight' => 1, 'color' => '#ef4444', 'icon' => '💎'],
        ];

        foreach ($dailyPrizes as $prize) {
            WheelPrize::create([
                'spin_wheel_id' => $dailyWheel->id,
                'name' => $prize['name'],
                'type' => $prize['type'],
                'value' => $prize['value'],
                'probability_weight' => $prize['weight'],
                'color' => $prize['color'],
                'icon' => $prize['icon'],
                'is_active' => true,
            ]);
        }

        // Create Premium Wheel
        $premiumWheel = SpinWheel::create([
            'name' => 'Premium Spin',
            'type' => 'premium',
            'cost' => 5.00,
            'cooldown_hours' => 0,
            'is_active' => true,
        ]);

        // Premium wheel prizes - balanced rewards (sustainable economics)
        $premiumPrizes = [
            ['name' => 'Try Again!', 'type' => 'nothing', 'value' => 0, 'weight' => 25, 'color' => '#6b7280', 'icon' => '😢'],
            ['name' => '$0.25 Credit', 'type' => 'wallet_credit', 'value' => 0.25, 'weight' => 25, 'color' => '#3b82f6', 'icon' => '💵'],
            ['name' => '$0.50 Credit', 'type' => 'wallet_credit', 'value' => 0.50, 'weight' => 20, 'color' => '#10b981', 'icon' => '💵'],
            ['name' => '$1.00 Credit', 'type' => 'wallet_credit', 'value' => 1.00, 'weight' => 15, 'color' => '#8b5cf6', 'icon' => '💰'],
            ['name' => '$2.00 Credit', 'type' => 'wallet_credit', 'value' => 2.00, 'weight' => 10, 'color' => '#f59e0b', 'icon' => '💰'],
            ['name' => '$5.00 Credit', 'type' => 'wallet_credit', 'value' => 5.00, 'weight' => 4, 'color' => '#ef4444', 'icon' => '💎'],
            ['name' => '$10.00 JACKPOT!', 'type' => 'wallet_credit', 'value' => 10.00, 'weight' => 1, 'color' => '#ec4899', 'icon' => '🎰'],
        ];

        foreach ($premiumPrizes as $prize) {
            WheelPrize::create([
                'spin_wheel_id' => $premiumWheel->id,
                'name' => $prize['name'],
                'type' => $prize['type'],
                'value' => $prize['value'],
                'probability_weight' => $prize['weight'],
                'color' => $prize['color'],
                'icon' => $prize['icon'],
                'is_active' => true,
            ]);
        }
    }
}
