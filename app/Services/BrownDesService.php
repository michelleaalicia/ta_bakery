<?php

namespace App\Services;

use InvalidArgumentException;

class BrownDesService
{
    /**
     * Brown's Double Exponential Smoothing, initialized S'1 = S''1 = X1.
     * Forecast in each history row is one-step-ahead from the previous period.
     * MAPE excludes zero actuals; MAE includes them.
     */
    public function calculate(array $values, float $alpha, int $horizon = 1): array
    {
        if (count($values) < 2 || $alpha <= 0 || $alpha >= 1 || $horizon < 1) {
            throw new InvalidArgumentException('Parameter DES tidak valid.');
        }

        $first = (float) $values[0];
        $second = $first;
        $rows = [];
        $absoluteErrors = [];
        $percentageErrors = [];

        foreach (array_values($values) as $index => $rawValue) {
            $actual = (float) $rawValue;
            $predicted = $index === 0 ? null : $level + $trend;

            if ($index > 0) {
                $first = $alpha * $actual + (1 - $alpha) * $first;
                $second = $alpha * $first + (1 - $alpha) * $second;
            }

            $level = 2 * $first - $second;
            $trend = $alpha / (1 - $alpha) * ($first - $second);

            $error = $predicted === null ? null : abs($actual - $predicted);
            if ($error !== null) {
                $absoluteErrors[] = $error;
                if ($actual > 0) {
                    $percentageErrors[] = ($error / $actual) * 100;
                }
            }

            $rows[] = [
                't' => $index + 1,
                'actual' => $actual,
                's1' => $first,
                's2' => $second,
                'level' => $level,
                'trend' => $trend,
                'forecast' => $predicted,
                'abs_error' => $error,
                'ape' => $actual > 0 && $error !== null ? $error / $actual * 100 : null,
            ];
        }

        $future = [];
        for ($step = 1; $step <= $horizon; $step++) {
            $future[] = [
                'step' => $step,
                'raw' => $level + $trend * $step,
                'units' => max(0, (int) ceil($level + $trend * $step)),
            ];
        }

        return [
            'alpha' => $alpha,
            'rows' => $rows,
            'mape' => count($percentageErrors) ? array_sum($percentageErrors) / count($percentageErrors) : null,
            'mae' => count($absoluteErrors) ? array_sum($absoluteErrors) / count($absoluteErrors) : null,
            'mape_count' => count($percentageErrors),
            'evaluation_count' => count($absoluteErrors),
            'future' => $future,
        ];
    }

    public function bestAlpha(array $values, int $horizon = 1): array
    {
        $best = null;
        foreach ([0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9] as $alpha) {
            $candidate = $this->calculate($values, $alpha, $horizon);
            // MAE is defined even if some or all actuals are zero.
            // Consistent one-step-ahead criterion for every candidate.
            if ($best === null || $candidate['mae'] < $best['mae'] - 0.0000001) {
                $best = $candidate;
            }
        }
        return $best;
    }
}
