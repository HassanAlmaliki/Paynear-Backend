<?php

namespace App\Services;

use App\Models\Agent;

class CommissionService
{
    /**
     * Calculate commission based on tiered fixed rules.
     * Rules:
     * - Amount <= 10,000 YER → Commission = 50 YER
     * - Amount > 10,000 and <= 100,000 YER → Commission = 100 YER
     * - Amount > 100,000 YER → Commission = 300 YER
     */
    public function calculateCommission(Agent $agent, float $amount): float
    {
        // Use the tiered fixed commission structure
        if ($amount <= 10000) {
            return 50;
        } elseif ($amount <= 100000) {
            return 100;
        } else {
            return 300;
        }
    }

    /**
     * Get commission display text for agent panel.
     */
    public function getCommissionBreakdown(Agent $agent, float $amount): array
    {
        $commission = $this->calculateCommission($agent, $amount);
        $totalDeducted = $amount + $commission;

        return [
            'requested_amount' => $amount,
            'commission_amount' => $commission,
            'total_deducted_amount' => $totalDeducted,
            'commission_description' => $this->getCommissionDescription($amount),
        ];
    }

    /**
     * Get human-readable commission description.
     */
    private function getCommissionDescription(float $amount): string
    {
        if ($amount <= 10000) {
            return 'عمولة ثابتة 50 ريال يمني (مبلغ أقل من أو يساوي 10,000)';
        } elseif ($amount <= 100000) {
            return 'عمولة ثابتة 100 ريال يمني (مبلغ أقل من أو يساوي 100,000)';
        } else {
            return 'عمولة ثابتة 300 ريال يمني (مبلغ أكبر من 100,000)';
        }
    }
}
