<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CourrierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::prefix('courriers')->name('courriers.')->group(function () {
        Route::get('/', [CourrierController::class, 'index'])
            ->middleware('permission:courrier.view')
            ->name('index');

        Route::get('/create', [CourrierController::class, 'create'])
            ->middleware('permission:courrier.create')
            ->name('create');

        Route::post('/', [CourrierController::class, 'store'])
            ->middleware('permission:courrier.create')
            ->name('store');

        Route::get('/{courrier}', [CourrierController::class, 'show'])
            ->middleware('permission:courrier.view')
            ->name('show');

        Route::post('/{courrier}/assign', [CourrierController::class, 'assign'])
            ->middleware('permission:courrier.assign')
            ->name('assign');

        Route::post('/{courrier}/submit-validation', [CourrierController::class, 'submitForValidation'])
            ->middleware('permission:courrier.process')
            ->name('submit_validation');

        Route::post('/{courrier}/validate', [CourrierController::class, 'validateCourrier'])
            ->middleware('permission:courrier.validate')
            ->name('validate');

        Route::post('/{courrier}/reject', [CourrierController::class, 'rejectCourrier'])
            ->middleware('permission:courrier.validate')
            ->name('reject');

        Route::post('/{courrier}/archive', [CourrierController::class, 'archive'])
            ->middleware('permission:courrier.archive')
            ->name('archive');

        Route::get('/export/excel', [CourrierController::class, 'exportExcel'])
            ->middleware('permission:courrier.export')
            ->name('export.excel');

        Route::get('/export/pdf', [CourrierController::class, 'exportPdf'])
            ->middleware('permission:courrier.export')
            ->name('export.pdf');

        Route::get('/export/excel', [CourrierController::class, 'exportExcel'])
            ->middleware('permission:courrier.export')
            ->name('export.excel');

        Route::get('/export/pdf', [CourrierController::class, 'exportPdf'])
            ->middleware('permission:courrier.export')
            ->name('export.pdf');


        // Attachments (IMPORTANT: preview BEFORE download)
        Route::post('/{courrier}/attachments', [CourrierController::class, 'uploadAttachment'])
            ->middleware('permission:courrier.attach')
            ->name('attachments.upload');

        Route::get('/{courrier}/attachments/{attachment}/preview', [CourrierController::class, 'previewAttachment'])
            ->middleware('permission:courrier.view')
            ->name('attachments.preview');

        Route::get('/{courrier}/attachments/{attachment}', [CourrierController::class, 'downloadAttachment'])
            ->middleware('permission:courrier.view')
            ->name('attachments.download');

        Route::delete('/{courrier}/attachments/{attachment}', [CourrierController::class, 'deleteAttachment'])
            ->middleware('permission:courrier.detach')
            ->name('attachments.delete');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/locale', function (Request $request) {
    $locale = (string) $request->input('locale', config('app.fallback_locale', 'fr'));

    if (! in_array($locale, ['fr', 'en'], true)) {
        $locale = config('app.fallback_locale', 'fr');
    }

    session(['locale' => $locale]);

    if ($request->user()) {
        $request->user()->forceFill(['locale' => $locale])->save();
    }

    return back();
})->name('locale.set');

require __DIR__ . '/auth.php';
