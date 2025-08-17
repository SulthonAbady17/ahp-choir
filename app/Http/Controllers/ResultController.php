<?php

namespace App\Http\Controllers;

use App\Models\Alternative;
use App\Models\AlternativeComparison;
use App\Models\Criterion;
use App\Models\CriterionComparison;
use App\Services\AHPCalculationService;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function calculate(AHPCalculationService $ahp)
    {
        // Ambil data master
        $criteria = Criterion::orderBy('id')->pluck('id')->all();
        $alternatives = Alternative::orderBy('id')->pluck('id')->all();
        $criterionComparisons = CriterionComparison::select(['id', 'criterion_left_id', 'criterion_right_id', 'value'])->get();
        $alternativeComparisons = AlternativeComparison::select(['id', 'alternative_left_id', 'alternative_right_id', 'value'])->get();

        // Susun pairs kriteria
        $criteriaPairs = [];
        foreach ($criterionComparisons as $row) {
            $criteriaPairs[$row->criterion_left_id][$row->criterion_right_id] = (float) $row->value;
        }

        // Susun pairs alternatif per kriteria
        $alternativePairsByCriteria = [];
        foreach ($alternativeComparisons as $row) {
            $alternativePairsByCriteria[$row->criterion_id][$row->alternative_left_id][$row->alternative_right_id] = (float) $row->value;
        }

        // Jalankan perhitungan
        $result = $ahp->calculateGlobalScores($criteria, $alternatives, $criteriaPairs, $alternativePairsByCriteria);

        // Validasi CR kriteria
        $criteriaCR = $result['detail']['consistency']['criteria']['cr'] ?? 1;
        if ($criteriaCR > 0.1) {
            return back()->with('error', 'CR matriks kriteria terlalu tinggi (' . number_format($criteriaCR, 4) . ').');
        }

        // Validasi CR alternatif per kriteria
        foreach ($criteria as $cid) {
            $cr = $result['detail']['consistency']['alternatives'][$cid]['cr'] ?? 1;
            if ($cr > 0.1) {
                return back()->with('error', "CR alternatif untuk kriteria $cid terlalu tinggi (" . number_format($cr, 4) . ").");
            }
        }
    }
}
