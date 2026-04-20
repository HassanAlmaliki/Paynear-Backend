<?php

namespace App\Filament\AgentPanel\Resources;

use App\Filament\AgentPanel\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    

    

    

    

        public static function getNavigationGroup(): ?string
    {
        return __('messages.user_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.customers');
    }

    public static function getModelLabel(): string
    {
        return __('messages.customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.customers');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('messages.basic_info'))
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label(__('messages.full_name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('messages.phone'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),
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
                Tables\Columns\TextColumn::make('id')
                    ->label(__('messages.id'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('messages.full_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('messages.phone'))
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
                    ->visible(fn (User $record) => $record->status === 'inactive')
                    ->action(fn (User $record) => $record->update(['status' => 'active'])),
                \Filament\Actions\Action::make('deactivate')
                    ->label(__('messages.deactivate'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record) => $record->status === 'active')
                    ->action(fn (User $record) => $record->update(['status' => 'inactive'])),
            ])
            ->bulkActions([
                // Agents cannot delete users
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('messages.basic_info'))
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')->label(__('messages.full_name')),
                        Infolists\Components\TextEntry::make('phone')->label(__('messages.phone')),
                        Infolists\Components\IconEntry::make('is_verified')->label(__('messages.verified'))->boolean(),
                        Infolists\Components\TextEntry::make('status')->label(__('messages.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'danger',
                                'suspended' => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')->label(__('messages.registration_date'))->dateTime('Y-m-d H:i'),
                    ])->columns(3),
                \Filament\Schemas\Components\Section::make(__('messages.wallet'))
                    ->schema([
                        Infolists\Components\TextEntry::make('wallet.balance')->label(__('messages.balance'))->money('YER'),
                        Infolists\Components\TextEntry::make('wallet.currency')->label(__('messages.currency')),
                        Infolists\Components\TextEntry::make('wallet.status')->label(__('messages.wallet_status')),
                    ])->columns(3),
                \Filament\Schemas\Components\Section::make(__('messages.kyc_data'))
                    ->schema([
                        Infolists\Components\TextEntry::make('profile.id_type')->label(__('messages.id_type')),
                        Infolists\Components\TextEntry::make('profile.id_number')->label(__('messages.id_number')),
                        Infolists\Components\TextEntry::make('profile.nationality')->label(__('messages.nationality')),
                        Infolists\Components\TextEntry::make('profile.address')->label(__('messages.address')),
                        Infolists\Components\TextEntry::make('profile.dob')->label(__('messages.dob'))->date(),
                        Infolists\Components\TextEntry::make('profile.verification_status')->label(__('messages.verification_status'))
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'pending_verification' => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\ImageEntry::make('profile.id_front_image')->label(__('messages.id_front_image'))->disk('public'),
                        Infolists\Components\ImageEntry::make('profile.id_back_image')->label(__('messages.id_back_image'))->disk('public'),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
