<?php

use App\Http\Controllers\AlternativeComparisonController;
use App\Http\Controllers\CriterionComparisonController;
use App\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

Route::get('/comparison/criteria', [CriterionComparisonController::class, 'index'])->name('comparison.criteria.index');
Route::post('/comparison/criteria', [CriterionComparisonController::class, 'store'])->name('comparison.criteria.store');
Route::post('/comparison/criteria/check-consistency', [CriterionComparisonController::class, 'checkConsistency'])->name('comparison.criteria.check');

// routes/web.php

// Menampilkan form perbandingan alternatif untuk kriteria tertentu
Route::get('/comparison/alternatives/{criterion}', [AlternativeComparisonController::class, 'show'])
    ->name('comparison.alternatives.show');

// Menyimpan data perbandingan alternatif
Route::post('/comparison/alternatives/{criterion}', [AlternativeComparisonController::class, 'store'])
    ->name('comparison.alternatives.store');

// Endpoint AJAX untuk cek konsistensi alternatif
Route::post('/comparison/alternatives/{criterion}/check-consistency', [AlternativeComparisonController::class, 'checkConsistency'])
    ->name('comparison.alternatives.check');

Route::get('/result', [ResultController::class, 'calculate']);
