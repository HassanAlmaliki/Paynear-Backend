<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Models\Agent;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-user-group';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.user_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.agents');
    }

    public static function getModelLabel(): string
    {
        return __('messages.agent');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.agents');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('messages.basic_info'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('messages.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('username')
                            ->label(__('messages.username'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
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
                            ])
                            ->default('active')
                            ->required(),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make(__('messages.commission_settings'))
                    ->description('العمولة الثابتة: ≤10,000 → 50 ريال | ≤100,000 → 100 ريال | >100,000 → 300 ريال')
                    ->schema([
                        Forms\Components\Select::make('commission_type')
                            ->label(__('messages.commission_type'))
                            ->options([
                                'fixed' => __('messages.fixed_commission'),
                            ])
                            ->default('fixed')
                            ->required(),
                        Forms\Components\Placeholder::make('commission_rules')
                            ->label(__('messages.commission_rules'))
                            ->content('• مبلغ ≤ 10,000 ريال → عمولة 50 ريال يمني
• مبلغ > 10,000 و ≤ 100,000 ريال → عمولة 100 ريال يمني
• مبلغ > 100,000 ريال → عمولة 300 ريال يمني'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label(__('messages.id'))->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label(__('messages.username'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('messages.phone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('messages.active'),
                        'inactive' => __('messages.inactive'),
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('agentWallet.balance')
                    ->label(__('messages.commissions_balance'))
                    ->money('YER')
                    ->default(0),
                Tables\Columns\TextColumn::make('agentWallet.total_earned')
                    ->label(__('messages.total_earned'))
                    ->money('YER')
                    ->default(0),
                Tables\Columns\TextColumn::make('withdrawals_count')
                    ->label(__('messages.operations_count'))
                    ->counts('withdrawals'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.creation_date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('messages.status'))
                    ->options([
                        'active' => __('messages.active'),
                        'inactive' => __('messages.inactive'),
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('messages.agent_data'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')->label(__('messages.name')),
                        Infolists\Components\TextEntry::make('username')->label(__('messages.username')),
                        Infolists\Components\TextEntry::make('phone')->label(__('messages.phone')),
                        Infolists\Components\TextEntry::make('status')->label(__('messages.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make(__('messages.commissions'))
                    ->schema([
                        Infolists\Components\TextEntry::make('agentWallet.balance')->label(__('messages.commissions_balance'))->money('YER'),
                        Infolists\Components\TextEntry::make('agentWallet.total_earned')->label(__('messages.total_earned'))->money('YER'),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make(__('messages.last_operations'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('withdrawals')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('requested_amount')->label(__('messages.amount'))->money('YER'),
                                Infolists\Components\TextEntry::make('commission_amount')->label(__('messages.commission'))->money('YER'),
                                Infolists\Components\TextEntry::make('status')->label(__('messages.status')),
                                Infolists\Components\TextEntry::make('created_at')->label(__('messages.date'))->dateTime('Y-m-d H:i'),
                            ])->columns(4),
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
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'view' => Pages\ViewAgent::route('/{record}'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
