<?php

namespace App\Filament\AgentPanel\Resources;

use App\Filament\AgentPanel\Resources\MerchantResource\Pages;
use App\Models\Merchant;
use App\Models\Device;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    

    

    

    

        public static function getNavigationGroup(): ?string
    {
        return __('messages.user_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.merchants');
    }

    public static function getModelLabel(): string
    {
        return __('messages.merchant');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.merchants');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('messages.basic_info'))
                    ->schema([
                        Forms\Components\TextInput::make('merchant_name')
                            ->label(__('messages.merchant_name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('messages.phone'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),
                        Forms\Components\TextInput::make('license_number')
                            ->label(__('messages.license_number'))
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\TextInput::make('password')
                            ->label(__('messages.password'))
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label(__('messages.status'))
                            ->options([
                                'active' => __('messages.active'),
                                'inactive' => __('messages.inactive'),
                                'suspended' => __('messages.suspended'),
                            ])
                            ->default('active')
                            ->required(),
                        Forms\Components\Toggle::make('is_verified')
                            ->label(__('messages.verified'))
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label(__('messages.id'))->sortable(),
                Tables\Columns\TextColumn::make('merchant_name')
                    ->label(__('messages.merchant_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('messages.phone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('license_number')
                    ->label(__('messages.license_number'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label(__('messages.verified'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'suspended' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('messages.active'),
                        'inactive' => __('messages.inactive'),
                        'suspended' => __('messages.suspended'),
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('wallet.balance')
                    ->label(__('messages.balance'))
                    ->money('YER')
                    ->default(0),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.registration_date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('messages.status'))
                    ->options([
                        'active' => __('messages.active'),
                        'inactive' => __('messages.inactive'),
                        'suspended' => __('messages.suspended'),
                    ]),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label(__('messages.verification_status')),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('activate')
                    ->label(__('messages.activate'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Merchant $record) => $record->status === 'inactive')
                    ->action(fn (Merchant $record) => $record->update(['status' => 'active'])),
                \Filament\Actions\Action::make('deactivate')
                    ->label(__('messages.deactivate'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Merchant $record) => $record->status === 'active')
                    ->action(fn (Merchant $record) => $record->update(['status' => 'inactive'])),
                \Filament\Actions\Action::make('linkDevice')
                    ->label(__('messages.link_device'))
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('device_serial')
                            ->label(__('messages.device_serial'))
                            ->required(),
                    ])
                    ->action(function (Merchant $record, array $data): void {
                        $record->devices()->create([
                            'serial_number' => $data['device_serial'], // Changed from device_serial to serial_number to match Device model
                            'status' => 'active',
                        ]);
                    }),
            ])
            ->bulkActions([
                // Agents cannot delete merchants
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('messages.basic_info'))
                    ->schema([
                        Infolists\Components\TextEntry::make('merchant_name')->label(__('messages.merchant_name')),
                        Infolists\Components\TextEntry::make('phone')->label(__('messages.phone')),
                        Infolists\Components\TextEntry::make('license_number')->label(__('messages.license_number')),
                        Infolists\Components\IconEntry::make('is_verified')->label(__('messages.verified'))->boolean(),
                        Infolists\Components\TextEntry::make('status')->label(__('messages.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'danger',
                                'suspended' => 'warning',
                                default => 'gray',
                            }),
                    ])->columns(3),
                \Filament\Schemas\Components\Section::make(__('messages.wallet'))
                    ->schema([
                        Infolists\Components\TextEntry::make('wallet.balance')->label(__('messages.balance'))->money('YER'),
                        Infolists\Components\TextEntry::make('wallet.currency')->label(__('messages.currency')),
                        Infolists\Components\TextEntry::make('wallet.status')->label(__('messages.wallet_status')),
                    ])->columns(3),
                \Filament\Schemas\Components\Section::make(__('messages.linked_devices'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('devices')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('serial_number')->label(__('messages.serial_number')),
                                Infolists\Components\TextEntry::make('status')->label(__('messages.status')),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchants::route('/'),
            'create' => Pages\CreateMerchant::route('/create'),
            'view' => Pages\ViewMerchant::route('/{record}'),
            'edit' => Pages\EditMerchant::route('/{record}/edit'),
        ];
    }
}
