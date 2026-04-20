<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Agent;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Super Admin (for Filament admin panel login)
        $admin = \App\Models\Admin::create([
            'name' => 'مدير النظام',
            'email' => 'admin@paynear.com',
            'password' => 'password',
        ]);

        // 2. Create Demo Agent (for Filament agent panel login)
        $agent = Agent::create([
            'name' => 'وكيل تجريبي',
            'username' => 'agent1',
            'password' => 'password',
            'phone' => '777111111',
            'commission_type' => 'fixed',
            'commission_value' => 0,
            'status' => 'active',
        ]);
        $agent->getOrCreateAgentWallet();

        // 3. Create Demo Users
        $user1 = User::create([
            'full_name' => 'أحمد محمد',
            'phone' => '777222222',
            'password' => 'password',
            'is_verified' => true,
            'status' => 'active',
        ]);
        $wallet1 = $user1->getOrCreateWallet();
        $wallet1->update(['balance' => 50000]);

        $user2 = User::create([
            'full_name' => 'سارة علي',
            'phone' => '777333333',
            'password' => 'password',
            'is_verified' => false,
            'status' => 'active',
        ]);
        $wallet2 = $user2->getOrCreateWallet();
        $wallet2->update(['balance' => 100000]);

        // 4. Create Demo Merchant
        $merchant = Merchant::create([
            'merchant_name' => 'متجر الكتروني',
            'phone' => '777444444',
            'password' => 'password',
            'license_number' => 'LIC-001',
            'is_verified' => true,
            'status' => 'active',
        ]);
        $merchantWallet = $merchant->getOrCreateWallet();
        $merchantWallet->update(['balance' => 200000]);

        // 5. Create a demo NFC card for user1
        $wallet1->cards()->create([
            'nfc_uid' => 'DEMO-NFC-UID-001',
            'status' => 'active',
            'expires_at' => now()->addYears(2),
        ]);

        echo "✅ Seeder completed:\n";
        echo "  Admin: email=admin@paynear.com, password=password\n";
        echo "  Agent: username=agent1, password=password\n";
        echo "  User1: phone=777222222, password=password (balance: 50,000 YER)\n";
        echo "  User2: phone=777333333, password=password (balance: 100,000 YER)\n";
        echo "  Merchant: phone=777444444, password=password (balance: 200,000 YER)\n";
    }
}
