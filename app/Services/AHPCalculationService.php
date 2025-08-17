<?php

namespace App\Services;

use InvalidArgumentException;

class AHPCalculationService
{
    /** Random Index (Saaty) untuk n=1..15 */
    private const RANDOM_INDEX = [
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
        11 => 1.51,
        12 => 1.48,
        13 => 1.56,
        14 => 1.57,
        15 => 1.59,
    ];

    /**
     * Bangun matriks NxN dari pasangan perbandingan.
     *
     * @param  array  $items  ordered IDs
     * @param  array  $pairs  [leftId => [rightId => value]]
     */
    public function buildMatrix(array $items, array $pairs): array
    {
        $count = count($items);
        $matrix = array_fill(0, $count, array_fill(0, $count, 1.0));

        foreach ($items as $i => $left) {
            foreach ($items as $j => $right) {
                if ($i === $j) {
                    continue;
                }
                $value = $pairs[$left][$right] ?? null;
                if ($i < $j && $value !== null) {
                    $matrix[$i][$j] = (float) $value;
                    $matrix[$j][$i] = 1.0 / $value;
                }
            }
        }

        return $matrix;
    }

    /**
     * Hitung bobot prioritas dengan metode rata-rata baris (eigenvector aproksimasi).
     */
    public function calculatePriorityVector(array $matrix): array
    {
        $normalized = $this->normalizeMatrixByColumns($matrix);
        $weights = array_map(
            fn($row) => array_sum($row) / count($row),
            $normalized
        );

        return $this->normalizeVector($weights);
    }

    /**
     * Hitung Consistency Ratio (CR).
     */
    public function calculateConsistencyRatio(array $matrix, array $weights): array
    {
        $n = count($matrix);
        if ($n < 2) {
            throw new InvalidArgumentException('Matrix size must be >= 2');
        }

        $lambdaMax = $this->calculateLambdaMax($matrix, $weights);
        $ci = ($lambdaMax - $n) / ($n - 1);
        $ri = self::RANDOM_INDEX[$n] ?? end(self::RANDOM_INDEX);
        $cr = ($ri > 0) ? $ci / $ri : 0.0;

        return [
            'lambda_max' => $lambdaMax,
            'ci' => $ci,
            'cr' => $cr,
        ];
    }

    /**
     * Hitung skor global alternatif berdasarkan bobot kriteria & alternatif.
     */
    public function calculateGlobalScores(
        array $criteriaIds,
        array $alternativeIds,
        array $criteriaPairs,
        array $alternativePairsByCriteria
    ): array {
        // 1. Hitung bobot kriteria
        $criteriaMatrix = $this->buildMatrix($criteriaIds, $criteriaPairs);
        $criteriaWeights = $this->calculatePriorityVector($criteriaMatrix);
        $criteriaConsistency = $this->calculateConsistencyRatio($criteriaMatrix, $criteriaWeights);

        // 2. Hitung bobot alternatif per kriteria
        $alternativeLocalWeights = [];
        $alternativeConsistency = [];
        foreach ($criteriaIds as $index => $criterionId) {
            $altMatrix = $this->buildMatrix($alternativeIds, $alternativePairsByCriteria[$criterionId] ?? []);
            $weights = $this->calculatePriorityVector($altMatrix);
            $alternativeLocalWeights[$criterionId] = $weights;
            $alternativeConsistency[$criterionId] = $this->calculateConsistencyRatio($altMatrix, $weights);
        }

        // 3. Agregasi skor global alternatif
        $globalScores = $this->aggregateAlternativeScores($criteriaWeights, $criteriaIds, $alternativeLocalWeights, count($alternativeIds));

        return [
            'scores' => array_combine($alternativeIds, $this->normalizeVector($globalScores)),
            'detail' => [
                'criteria_weights' => array_combine($criteriaIds, $criteriaWeights),
                'alt_local_weights' => array_map(
                    fn($cid) => array_combine($alternativeIds, $alternativeLocalWeights[$cid]),
                    array_keys($alternativeLocalWeights)
                ),
                'consistency' => [
                    'criteria' => $criteriaConsistency,
                    'alternatives' => $alternativeConsistency,
                ],
            ],
        ];
    }

    // ================== Private Helpers ==================

    private function normalizeMatrixByColumns(array $matrix): array
    {
        $n = count($matrix);
        $colSums = array_fill(0, $n, 0.0);

        foreach ($matrix as $row) {
            foreach ($row as $j => $value) {
                $colSums[$j] += $value;
            }
        }

        return array_map(
            fn($row) => array_map(
                fn($value, $j) => $value / ($colSums[$j] ?: 1),
                $row,
                array_keys($row)
            ),
            $matrix
        );
    }

    private function normalizeVector(array $vector): array
    {
        $sum = array_sum($vector) ?: 1;

        return array_map(fn($v) => $v / $sum, $vector);
    }

    private function calculateLambdaMax(array $matrix, array $weights): float
    {
        $Aw = [];
        foreach ($matrix as $i => $row) {
            $Aw[$i] = array_sum(array_map(fn($a, $w) => $a * $w, $row, $weights));
        }

        $ratios = [];
        foreach ($weights as $i => $w) {
            if ($w > 0) {
                $ratios[] = $Aw[$i] / $w;
            }
        }

        return array_sum($ratios) / count($ratios);
    }

    private function aggregateAlternativeScores(array $criteriaWeights, array $criteriaIds, array $localWeights, int $altCount): array
    {
        $scores = array_fill(0, $altCount, 0.0);

        foreach ($criteriaIds as $k => $criterionId) {
            $criterionWeight = $criteriaWeights[$k];
            foreach (range(0, $altCount - 1) as $i) {
                $scores[$i] += $criterionWeight * ($localWeights[$criterionId][$i] ?? 0);
            }
        }

        return $scores;
    }
}
