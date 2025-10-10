<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AIAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the AI Agent system user
        User::updateOrCreate(
            ['email' => 'agent@mmosupply.com'],
            [
                'name' => 'MMO Supply Agent',
                'username' => 'MMOSupplyAgent',
                'email' => 'agent@mmosupply.com',
                'password' => Hash::make(bin2hex(random_bytes(32))), // Random secure password
                'email_verified_at' => now(),
                'is_seller' => false,
                'role' => 'system', // System role for AI agent
                'avatar' => '/images/ai-agent-avatar.png', // You can set a custom avatar
                'active_title' => 'AI Assistant',
                'active_profile_theme' => 'agent_theme',
                'auto_tier' => 'premium', // Premium tier for AI agent
            ]
        );

        $this->command->info('✅ AI Agent system user created successfully');
    }
}
