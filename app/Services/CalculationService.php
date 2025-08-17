<?php

namespace App\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use MathPHP\LinearAlgebra\Eigenvalue;
use MathPHP\LinearAlgebra\Matrix;

class CalculationService
{
    private const RATIO_INDEX = [
        1 => 0.00,
        2 => 0.00,
        3 => 0.58,
        4 => 0.90,
        5 => 1.12,
        6 => 1.24,
        7 => 1.32,
        8 => 1.41,
        9 => 1.45,
        10 => 1.49,
    ];

    public function calculateWeightAndConsistency(array $comparisonValues, Collection $items): array
    {
        if ($items->count() < 2) {
            throw new InvalidArgumentException('Perhitungan AHP membutuhkan minimal 2 item');
        }

        $matrix = $this->buildComparisonMatrix($comparisonValues, $items);

        $eigenData = Eigenvalue::powerIteration($matrix);
        $lambdaMax = $eigenData[0]; // Eigenvalue dominan (λmax)
        $priorityVector = $this->normalizeVector($eigenData[1]); // Eigenvector yang dinormalisasi

        $consistency = $this->calculateConsistencyRatio($lambdaMax, $matrix->getN());

        if (! $consistency['is_consistent']) {
            // throw new InconsistentMatrixException(
            //     'Matriks perbandingan tidak konsisten. Mohon periksa kembali input Anda.',
            //     $consistency['ratio']
            // );
            dd('tidak konsisten');
        }

        $prioritiesWithIds = array_combine($items->pluck('id')->toArray(), $priorityVector);

        return [
            'priorities' => $prioritiesWithIds,
            'consistency_ratio' => $consistency['ratio'],
            'lambda_max' => $lambdaMax,
        ];
    }

    public function buildComparisonMatrix(array $comparisonValues, Collection $items): Matrix
    {
        $itemIds = $items->pluck('id')->toArray();
        $n = count($itemIds);
        $matrixArray = [];

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $rowId = $itemIds[$i];
                $colId = $itemIds[$j];

                if ($rowId === $colId) {
                    $matrixArray[$i][$j] = 1.0;
                } elseif ($rowId < $colId) {
                    $matrixArray[$i][$j] = (float) $comparisonValues[$rowId][$colId];
                } else {
                    $matrixArray[$i][$j] = 1.0 / (float) $comparisonValues[$colId][$rowId];
                }
            }
        }

        return new Matrix($matrixArray);
    }

    private function calculateConsistencyRatio(float $lambdaMax, int $n): array
    {
        if ($n <= 2) {
            return ['ratio' => 0.0, 'is_consistent' => true];
        }

        $consistencyIndex = ($lambdaMax - $n) / ($n - 1);
        $ratioIndex = self::RATIO_INDEX[$n] ?? 1.49;
        $consistencyRatio = ($ratioIndex > 0) ? $consistencyIndex / $ratioIndex : 0;

        return [
            'ratio' => $consistencyRatio,
            'is_consistent' => $consistencyRatio <= 0.1,
        ];
    }

    private function normalizeVector(array|float $vector): array
    {
        $sum = array_sum($vector);
        if ($sum == 0) {
            return $vector;
        }

        return array_map(fn ($v) => $v / $sum, $vector);
    }

    public function calculateFinalScores(array $criteriaPriorities, array $alternativePriorities, Collection $alternatives): array
    {
        $finalScores = [];
        foreach ($alternatives as $alternative) {
            $totalScore = 0.0;

            foreach ($criteriaPriorities as $criterionId => $criterionWeight) {
                $alternativeWeight = $alternativePriorities[$criterionId][$alternative->id] ?? 0.0;
                $totalScore += $criterionWeight * $alternativeWeight;
            }

            $finalScores[] = [
                'id' => $alternative->id,
                'name' => $alternative->name,
                'score' => $totalScore,
            ];
        }
        usort($finalScores, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $finalScores;
    }
}
