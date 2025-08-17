<?php

namespace App\Http\Controllers;

use App\Models\Alternative;
use App\Models\AlternativeComparison;
use App\Models\Criterion;
use App\Services\AHPCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AlternativeComparisonController extends Controller
{
    public function show(Criterion $criterion): View | RedirectResponse
    {
        // if (!session()->has('criteria_priorities')) {
        //     return redirect()->route('comparison.criteria.index')->with('error', 'Harap selesaikan perbandingan kriteria terlebih dahulu.');
        // }

        $alternatives = Alternative::orderBy('id')->get();

        if ($alternatives->count() < 2) {
            return redirect()->route('comparison.criteria.index')->with('Dibutuhkan minimal 2 alternatif untuk melakukan perbandingan.');
        }

        $existingComparisons = AlternativeComparison::select(['id', 'criterion_id', 'alternative_left_id', 'alternative_right_id', 'value'])->where('criterion_id', $criterion->id)->get()->keyBy(fn($item) => $item->alternative_left_id . '-' . $item->alternative_right_id);

        return view('pages.comparison.alternatives', compact('criterion', 'alternatives', 'existingComparisons'));
    }

    public function store(Request $request, Criterion $criterion)
    {
        $comparisonValues = $request->validate([
            'comparisons' => 'required|array',
        ]);

        // try {
        foreach ($comparisonValues['comparisons'] as $leftId => $rights) {
            foreach ($rights as $rightId => $value) {
                AlternativeComparison::updateOrCreate([
                    'criterion_id' => $criterion->id,
                    'alternative_left_id' => $leftId,
                    'alternative_right_id' => $rightId,
                    'value' => (float) $value,
                ]);
            };
        }

        $nextCriterionId = Criterion::where('id', '>', $criterion->id)->orderBy('id')->value('id');

        if ($nextCriterionId) {
            // Jika ada kriteria selanjutnya, redirect ke sana.
            return redirect()->route('comparison.alternatives.show', $nextCriterionId)
                ->with('success', 'Perbandingan untuk kriteria "' . $criterion->name . '" berhasil disimpan.');
        }

        // Jika ini kriteria terakhir, redirect ke halaman hasil.
        // return redirect()->route('result.index') // Pastikan route ini ada
        //     ->with('success', 'Semua perbandingan telah selesai. Lihat hasilnya di bawah.');
        // } catch (InconsistentMatrixException $e) {
        //     return back()->withErrors(['consistency' => 'Rasio Konsistensi (CR) melebihi 0.1 (' . number_format($e->consistencyRatio, 4) . '). Mohon periksa kembali input Anda.'])->withInput();
        // } catch (\Exception $e) {
        //     return back()->withErrors(['error' => 'Terjadi kesalahan tak terduga: ' . $e->getMessage()])->withInput();
        // }
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
