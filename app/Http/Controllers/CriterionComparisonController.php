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

        $existingComparisons = CriterionComparison::select(['id', 'criterion_left_id', 'criterion_right_id', 'value'])->get()->keyBy(fn($item) => $item->criterion_left_id . '-' . $item->criterion_right_id);

        return view('pages.comparison.criteria', compact('criteria', 'existingComparisons'));
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

        $criterion = Criterion::orderBy('id')->first('id');
        // dd($criterion->id);

        return redirect()->route('comparison.alternatives.show', ['criterion' => $criterion->id])->with('success', 'Perhitungan kriteria berhasil disimpan.');
    }

    public function checkConsistency(Request $request, AHPCalculationService $service)
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
