<?php

namespace App\Filament\Widgets;

use App\Models\UserProfile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;

class LatestKycRequests extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    
    // Set widget title
    protected function getTableHeading(): string|null
    {
        return __('messages.latest_kyc_requests');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UserProfile::query()
                    ->with('owner') // Eager load the polymorphic relationship
                    ->whereIn('verification_status', ['pending', 'pending_verification'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('owner_name')
                    ->label(__('messages.name'))
                    ->getStateUsing(function (UserProfile $record) {
                        if (!$record->owner) {
                            return __('messages.unknown');
                        }
                        
                        if (class_basename($record->owner_type) === 'User') {
                            return $record->owner->full_name;
                        } elseif (class_basename($record->owner_type) === 'Merchant') {
                            return $record->owner->merchant_name;
                        }
                        
                        return __('messages.unknown');
                    }),
                TextColumn::make('owner_type')
                    ->label(__('messages.account_type'))
                    ->formatStateUsing(function (string $state) {
                        return class_basename($state) === 'User' ? __('messages.user') : (class_basename($state) === 'Merchant' ? __('messages.merchant') : $state);
                    }),
                TextColumn::make('id_type')
                    ->label(__('messages.id_type'))
                    ->badge(),
                TextColumn::make('verification_status')
                    ->label(__('messages.status'))
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn () => __('messages.under_review')),
                TextColumn::make('created_at')
                    ->label(__('messages.submission_date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginated(false); // disable pagination since it's just the top 5
    }
}
