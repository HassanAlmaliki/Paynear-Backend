<?php

namespace App\Filament\AgentPanel\Resources\UserResource\Pages;

use App\Filament\AgentPanel\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // Auto-create wallet for the new user
        $this->record->getOrCreateWallet();
    }
}
