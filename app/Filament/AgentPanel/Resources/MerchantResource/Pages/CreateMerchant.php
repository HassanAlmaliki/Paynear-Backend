<?php
namespace App\Filament\AgentPanel\Resources\MerchantResource\Pages;
use App\Filament\AgentPanel\Resources\MerchantResource;
use Filament\Resources\Pages\CreateRecord;
class CreateMerchant extends CreateRecord
{
    protected static string $resource = MerchantResource::class;
    protected function afterCreate(): void
    {
        $this->record->getOrCreateWallet();
    }
}
