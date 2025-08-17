<?php

use App\Http\Controllers\CriterionComparisonController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CriterionComparisonController::class, 'index']);
Route::post('/', [CriterionComparisonController::class, 'store'])->name('criterion.store');
Route::post('/ahp/preview-cr', [CriterionComparisonController::class, 'previewCR'])->name('ahp.preview-cr');
