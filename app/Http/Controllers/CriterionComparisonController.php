<?php

namespace App\Http\Controllers;

use App\Models\Alternative;
use App\Models\AlternativeComparison;
use App\Models\Criterion;
use App\Models\CriterionComparison;
use App\Services\AHPCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CriterionComparisonController extends Controller
{
    public function index(): View
    {
        $criteria = Criterion::select(['id', 'name'])->get();

        return view('index', compact('criteria'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'comparisons' => 'required|array',
        ]);

        foreach ($data['comparisons'] as $leftId => $rights) {
            foreach ($rights as $rightId => $value) {
                CriterionComparison::updateOrCreate(
                    [
                        'criterion_left_id' => $leftId,
                        'criterion_right_id' => $rightId,
                    ],
                    [
                        'value' => (float) $value,
                    ]
                );
            }
        }

        return back()->with('success', 'Perhitungan kriteria berhasil disimpan.');
    }

    public function calculate(AHPCalculationService $ahp)
    {
        // Ambil data master
        $criteria = Criterion::pluck('id')->all();
        $alternatives = Alternative::orderBy('name')->pluck('id')->all();
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

        // Simpan hasil
        // DB::transaction(function () use ($result, $alternatives) {
        //     $session->results()->delete();

        //     $scores = $result['scores'];
        //     arsort($scores);

        //     $rank = 1;
        //     foreach ($scores as $altId => $score) {
        //         $session->results()->create([
        //             'id'             => (string) Str::uuid(),
        //             'alternative_id' => $altId,
        //             'score'          => round($score, 6),
        //             'rank'           => $rank++,
        //             'detail'         => $result['detail'],
        //         ]);
        //     }

        //     $session->update(['status' => 'finished']);
        // });

        // return redirect()->route('results.index', $session)->with('success', 'Perhitungan AHP selesai.');
    }

    public function previewCR(Request $request, AHPCalculationService $service)
    {
        $data = $request->validate(['comparisons' => 'required|array']);
        $ids = [];

        foreach ($data['comparisons'] as $l => $right) {
            $ids[] = $l;

            foreach (array_keys($right) as $r) {
                $ids[] = $r;
            }
        }

        $ids = array_values(array_unique($ids));

        $A = $service->buildMatrix($ids, $data['comparisons']);
        $w = $service->calculatePriorityVector($A);
        $cons = $service->calculateConsistencyRatio($A, $w);

        return response()->json([
            'cr' => $cons['cr'],
            'ok' => $cons['cr'] <= 0.1,
        ]);
    }
}
