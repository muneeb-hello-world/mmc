<?php

use Carbon\Carbon;

if (!function_exists('getShiftTimeRange')) {
    function getShiftTimeRange(int $shift, ?string $date = null): array
    {
        $date = $date ? Carbon::parse($date)->startOfDay() : Carbon::today();

        switch ($shift) {
            case 1: // Night Shift (Previous day 10 PM to current day 8 AM)
                $start = $date->copy()->subDay()->setTime(22, 0); // 10 PM previous day
                $end = $date->copy()->setTime(8, 0);              // 8 AM current day
                break;

            case 2: // Morning Shift (8 AM to 3 PM)
                $start = $date->copy()->setTime(8, 0);
                $end = $date->copy()->setTime(15, 0);
                break;

            case 3: // Evening Shift (3 PM to 10 PM)
                $start = $date->copy()->setTime(15, 0);
                $end = $date->copy()->setTime(22, 0);
                break;

            default:
                throw new InvalidArgumentException('Invalid shift number.');
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => match ($shift) {
                1 => 'Night (10 PM – 8 AM)',
                2 => 'Morning (8 AM – 3 PM)',
                3 => 'Evening (3 PM – 10 PM)',
            },
        ];
    }
}
