<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Merchant;
use App\Models\Agent;
use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make(__('messages.total_customers'), User::count())
                ->description(__('messages.registered_customers_count'))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make(__('messages.total_merchants'), Merchant::count())
                ->description(__('messages.registered_merchants_count'))
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('success'),
            Stat::make(__('messages.total_agents'), Agent::count())
                ->description(__('messages.registered_agents_count'))
                ->descriptionIcon('heroicon-m-identification')
                ->color('info'),
            Stat::make(__('messages.total_wallets_balance'), number_format(Wallet::sum('balance'), 2) . ' YER')
                ->description(__('messages.total_all_wallets_balances'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
            Stat::make(__('messages.today_transactions'), Transaction::whereDate('created_at', today())->count())
                ->description(__('messages.daily_transactions_count'))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('gray'),
        ];
    }
}
