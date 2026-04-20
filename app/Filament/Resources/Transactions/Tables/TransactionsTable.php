<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('type')
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
                    })
                    ->searchable(),
                TextColumn::make('original_amount')
                    ->label(__('messages.amount'))
                    ->money('YER')
                    ->sortable(),
                TextColumn::make('commission_amount')
                    ->label(__('messages.commission_amount'))
                    ->money('YER')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('messages.total_amount'))
                    ->money('YER')
                    ->sortable(),
                TextColumn::make('status')
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
                    })
                    ->searchable(),
                TextColumn::make('reference')
                    ->label(__('messages.reference_number'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('messages.date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([
                // Read-only
            ]);
    }
}
