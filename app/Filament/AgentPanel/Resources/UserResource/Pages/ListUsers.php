<?php

namespace App\Filament\AgentPanel\Resources\UserResource\Pages;

use App\Filament\AgentPanel\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('messages.add_customer')),
        ];
    }
}
