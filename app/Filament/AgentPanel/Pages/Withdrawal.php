<?php

namespace App\Filament\AgentPanel\Pages;

use App\Models\Agent;
use App\Models\Withdrawal as WithdrawalModel;
use App\Services\CommissionService;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class Withdrawal extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.agent-panel.pages.withdrawal';

    public ?array $data = [];
    public ?int $withdrawalId = null;
    public ?string $phase = 'initiate'; // initiate, verify
    public ?float $commissionAmount = null;
    public ?float $totalDeducted = null;
    public ?string $otpCode = null;

    public function getHeading(): string
    {
        return __('messages.withdrawal_operation');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.withdrawal');
    }

    public function mount(): void
    {
        $this->form->fill();
        $this->phase = 'initiate';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('messages.start_withdrawal'))
                    ->schema([
                        TextInput::make('phone')
                            ->label(__('messages.client_phone'))
                            ->required()
                            ->maxLength(20)
                            ->regex('/^(\+967|0)?7[0137]\d{7}$/')
                            // ->helperText('سيتم إضافة رمز الدولة (+967) تلقائياً')
                            ->mutateDehydratedStateUsing(function (?string $state) {
                                if (blank($state)) {
                                    return $state;
                                }
                                if (str_starts_with($state, '+967')) {
                                    return $state;
                                }
                                if (str_starts_with($state, '0')) {
                                    return '+967' . substr($state, 1);
                                }
                                if (str_starts_with($state, '7')) {
                                    return '+967' . $state;
                                }
                                return $state;
                            })
                            ->placeholder(__('messages.enter_phone'))
                            ->disabled($this->phase === 'verify'),
                        TextInput::make('amount')
                            ->label(__('messages.withdrawal_amount'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix('YER')
                            ->placeholder(__('messages.enter_amount'))
                            ->disabled($this->phase === 'verify')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state) {
                                if ($state && is_numeric($state)) {
                                    /** @var Agent $agent */
                                    $agent = auth()->guard('agent')->user();
                                    $commission = app(CommissionService::class)->calculateCommission($agent, (float) $state);
                                    $this->commissionAmount = $commission;
                                    $this->totalDeducted = (float) $state + $commission;
                                }
                            }),
                    ])->columns(2)
                    ->visible($this->phase === 'initiate'),
            ])
            ->statePath('data');
    }

    public function initiateWithdrawal(): void
    {
        $data = $this->form->getState();
        /** @var Agent $agent */
        $agent = auth()->guard('agent')->user();

        $walletService = app(WalletService::class);
        $withdrawalService = app(WithdrawalService::class);

        $wallet = $walletService->findWalletByPhone($data['phone']);

        if (!$wallet) {
            Notification::make()
                ->title(__('messages.error'))
                ->body(__('messages.user_not_found_simple'))
                ->danger()
                ->send();
            return;
        }

        try {
            $result = $withdrawalService->initiate($agent, $wallet, (float) $data['amount']);

            $this->withdrawalId = $result['withdrawal']->id;
            $this->commissionAmount = $result['commission_amount'];
            $this->totalDeducted = $result['total_deducted'];
            $this->phase = 'verify';

            Notification::make()
                ->title(__('messages.otp_sent'))
                ->body(__('messages.otp_sent_body', [
                    'commission' => $result['commission_amount'],
                    'total' => $result['total_deducted']
                ]))
                ->warning()
                ->persistent()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function verifyOtp(): void
    {
        if (!$this->withdrawalId || !$this->otpCode) {
            Notification::make()
                ->title(__('messages.error'))
                ->body(__('messages.enter_otp'))
                ->danger()
                ->send();
            return;
        }

        $withdrawal = WithdrawalModel::find($this->withdrawalId);
        if (!$withdrawal) {
            Notification::make()
                ->title(__('messages.error'))
                ->body(__('messages.withdrawal_not_found_error'))
                ->danger()
                ->send();
            return;
        }

        $withdrawalService = app(WithdrawalService::class);

        try {
            $withdrawalService->verifyAndComplete($withdrawal, $this->otpCode);

            Notification::make()
                ->title(__('messages.operation_success'))
                ->body(__('messages.withdrawal_success', [
                    'amount' => $withdrawal->requested_amount
                ]))
                ->success()
                ->send();

            $this->reset(['withdrawalId', 'phase', 'commissionAmount', 'totalDeducted', 'otpCode']);
            $this->phase = 'initiate';
            $this->form->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.verification_error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelWithdrawal(): void
    {
        if ($this->withdrawalId) {
            $withdrawal = WithdrawalModel::find($this->withdrawalId);
            if ($withdrawal) {
                app(WithdrawalService::class)->cancel($withdrawal);
            }
        }

        $this->reset(['withdrawalId', 'phase', 'commissionAmount', 'totalDeducted', 'otpCode']);
        $this->phase = 'initiate';
        $this->form->fill();

        Notification::make()
            ->title(__('messages.operation_cancelled'))
            ->info()
            ->send();
    }
}
