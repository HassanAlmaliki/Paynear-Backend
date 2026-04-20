<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KycRequestResource\Pages;
use App\Models\UserProfile;
use App\Services\KycService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;

class KycRequestResource extends Resource
{
    protected static ?string $model = UserProfile::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    public static function getNavigationGroup(): ?string
    {
        return __('messages.verification_requests');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.kyc_requests_nav');
    }

    public static function getModelLabel(): string
    {
        return __('messages.kyc_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.kyc_requests');
    }

    protected static ?string $slug = 'kyc-requests';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('verification_status', ['pending_verification', 'pending', 'approved', 'rejected'])
            ->latest();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label(__('messages.id'))->sortable(),
                Tables\Columns\TextColumn::make('owner_type')
                    ->label(__('messages.user_type'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'user' => __('messages.customer'),
                        'merchant' => __('messages.merchant'),
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'user' => 'info',
                        'merchant' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('owner_id')
                    ->label(__('messages.user_name'))
                    ->formatStateUsing(function (UserProfile $record): string {
                        $owner = $record->owner;
                        if (!$owner) return __('messages.unknown');
                        return $record->owner_type === 'user' ? $owner->full_name : $owner->merchant_name;
                    }),
                Tables\Columns\TextColumn::make('id_type')
                    ->label(__('messages.id_type')),
                Tables\Columns\TextColumn::make('verification_status')
                    ->label(__('messages.verification_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('messages.pending'),
                        'pending_verification' => __('messages.pending_verification'),
                        'approved' => __('messages.approved'),
                        'rejected' => __('messages.rejected'),
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.submission_date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label(__('messages.verification_status'))
                    ->options([
                        'pending_verification' => __('messages.pending_verification'),
                        'approved' => __('messages.approved'),
                        'rejected' => __('messages.rejected'),
                    ]),
                Tables\Filters\SelectFilter::make('owner_type')
                    ->label(__('messages.user_type'))
                    ->options([
                        'user' => __('messages.customer'),
                        'merchant' => __('messages.merchant'),
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()->label(__('messages.view')),
                \Filament\Actions\Action::make('approve')
                    ->label(__('messages.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UserProfile $record): bool => in_array($record->verification_status, ['pending', 'pending_verification']))
                    ->requiresConfirmation()
                    ->modalHeading(__('messages.confirm_approval'))
                    ->modalDescription(__('messages.approve_confirmation_text'))
                    ->action(function (UserProfile $record) {
                        app(KycService::class)->approve($record);
                    }),
                \Filament\Actions\Action::make('reject')
                    ->label(__('messages.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (UserProfile $record): bool => in_array($record->verification_status, ['pending', 'pending_verification']))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label(__('messages.rejection_reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (UserProfile $record, array $data) {
                        app(KycService::class)->reject($record, $data['rejection_reason']);
                    }),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('messages.user_data'))
                    ->schema([
                        Infolists\Components\TextEntry::make('owner_type')->label(__('messages.user_type'))
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'user' => __('messages.customer'),
                                'merchant' => __('messages.merchant'),
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('verification_status')->label(__('messages.verification_status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            }),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make(__('messages.identity_data'))
                    ->schema([
                        Infolists\Components\TextEntry::make('id_type')->label(__('messages.id_type')),
                        Infolists\Components\TextEntry::make('id_number')->label(__('messages.id_number')),
                        Infolists\Components\TextEntry::make('id_expiry_date')->label(__('messages.id_expiry_date'))->date(),
                        Infolists\Components\TextEntry::make('nationality')->label(__('messages.nationality')),
                        Infolists\Components\TextEntry::make('address')->label(__('messages.address')),
                        Infolists\Components\TextEntry::make('dob')->label(__('messages.dob'))->date(),
                    ])->columns(3),
                \Filament\Schemas\Components\Section::make(__('messages.document_images'))
                    ->schema([
                        Infolists\Components\ImageEntry::make('id_front_image')
                            ->label(__('messages.id_front_image'))
                            ->disk('public')
                            ->width(400)
                            ->height(250),
                        Infolists\Components\ImageEntry::make('id_back_image')
                            ->label(__('messages.id_back_image'))
                            ->disk('public')
                            ->width(400)
                            ->height(250),
                    ])->columns(2),
                \Filament\Schemas\Components\Section::make(__('messages.rejection_reason'))
                    ->schema([
                        Infolists\Components\TextEntry::make('rejection_reason')->label(__('messages.rejection_reason')),
                    ])
                    ->visible(fn (UserProfile $record): bool => $record->verification_status === 'rejected'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKycRequests::route('/'),
            'view' => Pages\ViewKycRequest::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
