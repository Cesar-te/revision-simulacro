<?php

use App\Http\Controllers\ExamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('exams.index');
});

Route::prefix('exams')->name('exams.')->group(function () {
    Route::get('/', [ExamController::class, 'index'])->name('index');
    Route::post('/', [ExamController::class, 'store'])->name('store');
    Route::get('/{exam}', [ExamController::class, 'show'])->name('show');
    Route::delete('/{exam}', [ExamController::class, 'destroy'])->name('destroy');
    
    Route::post('/{exam}/upload-keys', [ExamController::class, 'uploadKeys'])->name('upload-keys');
    Route::post('/{exam}/upload-responses', [ExamController::class, 'uploadResponses'])->name('upload-responses');
    Route::post('/{exam}/recalculate', [ExamController::class, 'recalculateAll'])->name('recalculate');
    Route::get('/{exam}/export', [ExamController::class, 'export'])->name('export');
    Route::get('/{exam}/student/{student}', [ExamController::class, 'studentDetail'])->name('student-detail');
});
