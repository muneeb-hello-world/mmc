<?php

namespace App\Traits;

use Carbon\Carbon;
use InvalidArgumentException;

trait ShiftGeter
{
    public function getShiftLabel($key)
{
    return match ($key) {
        'm' => '🌅 Morning (8 AM – 3 PM)',
        'e' => '🌇 Evening (3 PM – 10 PM)',
        'n' => '🌙 Night (10 PM – 8 AM)',
        default => 'Unknown Shift',
    };
}


    function getShift($date, $shift)
    {
        $date = Carbon::parse($date)->startOfDay();

        switch (strtolower($shift)) {
            case 'm': // morning
                $start = $date->copy()->setTime(8, 0);
                $end = $date->copy()->setTime(15, 0);
                break;

            case 'e': // evening
                $start = $date->copy()->setTime(15, 0);
                $end = $date->copy()->setTime(22, 0);
                break;

            case 'n': // night
                $start = $date->copy()->setTime(22, 0);
                $end = $date->copy()->addDay()->setTime(8, 0);
                break;

            default:
                throw new InvalidArgumentException("Invalid shift: $shift (use 'm', 'e', or 'n')");
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }
    function detectCurrentShift($now = null)
    {
        $now = $now ? Carbon::parse($now) : Carbon::now();

        $hour = $now->hour;
        $date = $now->copy()->startOfDay(); // default shift date

        if ($hour >= 8 && $hour < 15) {
            $shift = 'm';
            $start = $date->copy()->setTime(8, 0);
            $end = $date->copy()->setTime(15, 0);
        } elseif ($hour >= 15 && $hour < 22) {
            $shift = 'e';
            $start = $date->copy()->setTime(15, 0);
            $end = $date->copy()->setTime(22, 0);
        } else {
            // It's night
            $shift = 'n';

            // if between 00:00 and 07:59 → use previous date
            if ($hour < 8) {
                $date->subDay();
            }

            $start = $date->copy()->setTime(22, 0);
            $end = $date->copy()->addDay()->setTime(8, 0);
        }

        return [
            'shift' => $shift,
            'date' => $date->toDateString(),
            'start' => $start,
            'end' => $end,
        ];
    }
}
