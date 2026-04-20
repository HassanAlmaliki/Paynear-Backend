<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Models\Device;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-device-phone-mobile';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('messages.devices_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.payment_devices');
    }

    public static function getModelLabel(): string
    {
        return __('messages.payment_device');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.payment_devices');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('messages.device_data'))
                    ->schema([
                        Forms\Components\TextInput::make('serial_number')
                            ->label(__('messages.serial_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(__('messages.auto_generated'))
                            ->visibleOn('edit'),
                        Forms\Components\TextInput::make('api_key')
                            ->label(__('messages.api_key'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(__('messages.auto_generated'))
                            ->visibleOn('edit'),
                        Forms\Components\Select::make('merchant_id')
                            ->label(__('messages.linked_merchant'))
                            ->relationship('merchant', 'merchant_name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->label(__('messages.status'))
                            ->options([
                                'active' => __('messages.active'),
                                'inactive' => __('messages.inactive'),
                                'maintenance' => __('messages.maintenance'),
                            ])
                            ->default('active')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label(__('messages.id'))->sortable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label(__('messages.serial_number'))
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('api_key')
                    ->label(__('messages.api_key'))
                    ->limit(20)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('merchant.merchant_name')
                    ->label(__('messages.linked_merchant'))
                    ->default(__('messages.unlinked'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'maintenance' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('messages.active'),
                        'inactive' => __('messages.inactive'),
                        'maintenance' => __('messages.maintenance'),
                        default => $state,
                    }),
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
                        'maintenance' => __('messages.maintenance'),
                    ]),
                Tables\Filters\SelectFilter::make('merchant_id')
                    ->label(__('messages.merchant'))
                    ->relationship('merchant', 'merchant_name'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }
}
