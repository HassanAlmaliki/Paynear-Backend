<?php

namespace App\Filament\AgentPanel\Pages;

use App\Models\Withdrawal as WithdrawalModel;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class TransactionsLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    public function getHeading(): string
    {
        return __('messages.transactions_log');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.transactions_log');
    }

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.agent-panel.pages.transactions-log';

    public function table(Table $table): Table
    {
        $agent = auth()->guard('agent')->user();

        return $table
            ->query(
                WithdrawalModel::where('agent_id', $agent->id)
                    ->with(['wallet.owner'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('messages.id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('wallet.owner.full_name')
                    ->label(__('messages.client_name'))
                    ->default('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('requested_amount')
                    ->label(__('messages.withdrawal_amount'))
                    ->money('YER'),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label(__('messages.commission_amount'))
                    ->money('YER'),
                Tables\Columns\TextColumn::make('total_deducted_amount')
                    ->label(__('messages.total_deducted_amount'))
                    ->money('YER'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('messages.pending'),
                        'completed' => __('messages.completed'),
                        'failed' => __('messages.failed'),
                        'cancelled' => __('messages.cancelled'),
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('messages.status'))
                    ->options([
                        'pending' => __('messages.pending'),
                        'completed' => __('messages.completed'),
                        'failed' => __('messages.failed'),
                        'cancelled' => __('messages.cancelled'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
