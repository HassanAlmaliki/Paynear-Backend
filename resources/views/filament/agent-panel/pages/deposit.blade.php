<x-filament-panels::page>
    <form wire:submit="deposit">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                إيداع المبلغ
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
