<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('messages.transaction_info'))
                    ->schema([
                        TextEntry::make('id')->label(__('messages.transaction_id')),
                        TextEntry::make('type')
                            ->label(__('messages.transaction_type'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'deposit' => 'success',
                                'withdrawal' => 'danger',
                                'transfer' => 'info',
                                'payment' => 'warning',
                                'commission' => 'primary',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'deposit' => __('messages.deposit'),
                                'withdrawal' => __('messages.withdrawal'),
                                'transfer' => __('messages.transfer'),
                                'payment' => __('messages.payment'),
                                'commission' => __('messages.commission'),
                                default => $state,
                            }),
                        TextEntry::make('status')
                            ->label(__('messages.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'reversed' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'completed' => __('messages.completed'),
                                'pending' => __('messages.pending'),
                                'failed' => __('messages.failed'),
                                'reversed' => __('messages.reversed'),
                                default => $state,
                            }),
                        TextEntry::make('reference')
                            ->label(__('messages.reference_number'))
                            ->placeholder('-'),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make(__('messages.financial_details'))
                    ->schema([
                        TextEntry::make('original_amount')
                            ->label(__('messages.amount'))
                            ->money('YER'),
                        TextEntry::make('commission_amount')
                            ->label(__('messages.commission_amount'))
                            ->money('YER'),
                        TextEntry::make('total_amount')
                            ->label(__('messages.total_amount_deducted'))
                            ->money('YER'),
                    ])->columns(3),
                \Filament\Schemas\Components\Section::make(__('messages.transaction_parties'))
                    ->schema([
                        TextEntry::make('fromWallet.owner.full_name') // For user/agent
                            ->label(__('messages.from_account'))
                            ->placeholder(fn ($record) => $record->from_wallet_id ? null : __('messages.system')),
                        TextEntry::make('toWallet.owner.full_name')
                            ->label(__('messages.to_account'))
                            ->placeholder(fn ($record) => $record->to_wallet_id ? null : __('messages.system')),
                        TextEntry::make('created_at')
                            ->label(__('messages.execution_date'))
                            ->dateTime('d M Y - H:i A'),
                    ])->columns(3),
            ]);
    }
}
