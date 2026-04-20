<?php
namespace App\Filament\AgentPanel\Resources\MerchantResource\Pages;
use App\Filament\AgentPanel\Resources\MerchantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditMerchant extends EditRecord
{
    protected static string $resource = MerchantResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make()];
    }
}
