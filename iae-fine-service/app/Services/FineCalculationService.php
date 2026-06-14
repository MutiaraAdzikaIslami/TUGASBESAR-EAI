<?php

namespace App\Services;

class FineCalculationService
{
    const FINE_RATE_PER_DAY = 5000;

    public function calculate(int $daysLate): float
    {
        if ($daysLate <= 0) {
            return 0;
        }

        return $daysLate * self::FINE_RATE_PER_DAY;
    }

    public function getFineRate(): int
    {
        return self::FINE_RATE_PER_DAY;
    }
}
