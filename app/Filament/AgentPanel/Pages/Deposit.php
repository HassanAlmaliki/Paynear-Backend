<?php

namespace App\Filament\AgentPanel\Pages;

use App\Services\WalletService;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class Deposit extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.agent-panel.pages.deposit';

    public ?array $data = [];

    public function getHeading(): string
    {
        return __('messages.deposit_amount_title');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.deposit');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.deposit_operation'))
                    ->schema([
                        TextInput::make('phone')
                            ->label(__('messages.client_merchant_phone'))
                            ->required()
                            ->maxLength(20)
                            ->placeholder(__('messages.enter_phone')),
                        TextInput::make('amount')
                            ->label(__('messages.deposit_amount_label'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix('YER')
                            ->placeholder(__('messages.enter_amount')),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function deposit(): void
    {
        $data = $this->form->getState();

        $walletService = app(WalletService::class);

        $wallet = $walletService->findWalletByPhone($data['phone']);

        if (!$wallet) {
            Notification::make()
                ->title(__('messages.error'))
                ->body(__('messages.user_not_found'))
                ->danger()
                ->send();
            return;
        }

        try {
            $transaction = $walletService->deposit($wallet, (float) $data['amount']);

            Notification::make()
                ->title(__('messages.operation_success'))
                ->body(__('messages.deposit_success', [
                    'amount' => number_format($data['amount'], 2),
                    'reference' => $transaction->reference
                ]))
                ->success()
                ->send();

            $this->form->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
