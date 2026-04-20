<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;

class TransactionsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return __('messages.transactions_growth');
    }

    protected function getData(): array
    {
        $transactions = Transaction::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = [];
        $labels = [];

        // Fill in missing days
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->locale(app()->getLocale())->translatedFormat('M d');
            
            $record = $transactions->firstWhere('date', $date);
            $data[] = $record ? (int) $record->count : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => __('messages.transactions_count'),
                    'data' => $data,
                    'fill' => 'start',
                    'borderColor' => '#3CC7FD',
                    'backgroundColor' => 'rgba(60, 199, 253, 0.2)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
