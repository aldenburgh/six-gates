<?php

namespace SixGates\Utils;

class Statistics
{
    /**
     * Calculates the slope of the linear regression line for a series of numbers.
     * @param array $y Dependent variables (e.g., margins over time)
     * @return float The slope (growth/decline rate)
     */
    public static function linearRegressionSlope(array $y): float
    {
        $n = count($y);
        if ($n < 2) {
            return 0.0;
        }

        $x = range(0, $n - 1);

        $sumX = array_sum($x);
        $sumY = array_sum($y);

        $sumXX = 0;
        $sumXY = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXX += $x[$i] * $x[$i];
            $sumXY += $x[$i] * $y[$i];
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);

        if ($denominator == 0) {
            return 0.0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }
    public static function standardDeviation(array $a, bool $sample = false): float
    {
        $n = count($a);
        if ($n === 0) {
            return 0.0;
        }
        if ($sample && $n === 1) {
            return 0.0;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = $val - $mean;
            $carry += $d * $d;
        }
        if ($sample) {
            --$n;
        }
        return sqrt($carry / $n);
    }
}
