<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('from_wallet_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('to_wallet_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('original_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('commission_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('reference')
                    ->label(__('messages.reference_number'))
                    ->default(null),
            ]);
    }
}
