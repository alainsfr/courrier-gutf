<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CourrierController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\ChatWidgetController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BureauController as AdminBureauController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {



    // Dashboard (Dynamique et connecté à la base de données)
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();
        $isAdmin = $user->hasRole(['admin', 'super-admin']);

        // Requête de base pour les statistiques
        $query = \App\Models\Courrier::query();

        // Si l'utilisateur n'est pas Admin, on restreint à ses courriers (créés ou assignés)
        // + plis en validation pour les validateurs
        if (!$isAdmin) {
            $query->where(function ($sub) use ($user) {
                $sub->where('created_by_user_id', $user->id)
                    ->orWhere('assigned_to_user_id', $user->id);

                if ($user->can('courrier.validate')) {
                    $sub->orWhereHas('workflowEtape', fn ($e) => $e->where('ordre', \App\Models\Courrier::ETAPE_VALIDATION));
                }
            });
        }

        // Calcul des KPI réels (étapes workflow)
        $kpiIncoming = (clone $query)->where('type', 'entrant')->count();
        $kpiInProgress = (clone $query)->whereEtapeOrdreIn([
            \App\Models\Courrier::ETAPE_ANALYSE,
            \App\Models\Courrier::ETAPE_TRAITEMENT,
            \App\Models\Courrier::ETAPE_VALIDATION,
        ])->count();
        $kpiArchived = (clone $query)->where(function ($q) {
            $q->whereNotNull('archived_at')
                ->orWhereHas('workflowEtape', fn ($e) => $e->where('ordre', \App\Models\Courrier::ETAPE_ARCHIVAGE));
        })->count();

        // Liste des 5 derniers courriers nécessitant une attention
        $recentCourriers = (clone $query)
            ->with(['assignedTo:id,name', 'workflowEtape:id,libelle,ordre'])
            ->latest('id')
            ->limit(5)
            ->get();

        return view('dashboard', compact('kpiIncoming', 'kpiInProgress', 'kpiArchived', 'recentCourriers', 'isAdmin'));
    })->name('dashboard');

    // Notifications (in-app)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');

    // Statistiques & Rapports — réservé super-admin / directeur
    Route::middleware(['role:super-admin|directeur', 'permission:courrier.view'])->group(function () {
        Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');

        Route::get('/statistics/export/pdf', [StatisticsController::class, 'exportPdf'])
            ->middleware('permission:courrier.export')
            ->name('statistics.export.pdf');

        Route::get('/statistics/export/excel', [StatisticsController::class, 'exportExcel'])
            ->middleware('permission:courrier.export')
            ->name('statistics.export.excel');
    });

    // Audit (V1 - journal d'audit)
    Route::middleware('permission:auditor.view')->group(function () {
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

        Route::get('/audit/export/excel', [AuditController::class, 'exportExcel'])
            ->middleware('permission:auditor.export')
            ->name('audit.export.excel');

        Route::get('/audit/export/pdf', [AuditController::class, 'exportPdf'])
            ->middleware('permission:auditor.export')
            ->name('audit.export.pdf');
    });

    // --- Administration (gouvernance) ---
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super-admin'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('index');
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::resource('bureaux', AdminBureauController::class)
            ->except(['show'])
            ->parameters(['bureaux' => 'bureau']);
    });

    // --- Courriers ---
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

        // Exports AVANT /{courrier} pour éviter le shadowing du model binding
        Route::get('/export/excel', [CourrierController::class, 'exportExcel'])
            ->middleware('permission:courrier.export')
            ->name('export.excel');

        Route::get('/export/pdf', [CourrierController::class, 'exportPdf'])
            ->middleware('permission:courrier.export')
            ->name('export.pdf');

        Route::get('/kanban', [WorkflowController::class, 'kanban'])
            ->middleware('permission:courrier.view')
            ->name('kanban');

        Route::get('/search', [SearchController::class, 'index'])
            ->middleware('permission:courrier.view')
            ->name('search');

        Route::post('/{courrier}/transfer', [WorkflowController::class, 'transfer'])
            ->middleware('permission:courrier.view')
            ->name('transfer');

        Route::get('/{courrier}', [CourrierController::class, 'show'])
            ->middleware('permission:courrier.view')
            ->name('show');

        Route::post('/{courrier}/assign', [CourrierController::class, 'assign'])
            ->middleware('permission:courrier.assign')
            ->name('assign');

        // Processing
        Route::post('/{courrier}/start-processing', [CourrierController::class, 'startProcessing'])
            ->middleware('permission:courrier.process')
            ->name('start_processing');

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

        // Attachments (preview BEFORE download)
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

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Suppression de compte désactivée (application institutionnelle).
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Messaging
    Route::middleware('permission:chat.view')->group(function () {
        Route::get('/messages', [MessagingController::class, 'index'])->name('messages.index');
        Route::get('/messages/{conversation}', [MessagingController::class, 'show'])->name('messages.show');
    });

    Route::middleware('permission:chat.send')->group(function () {
        Route::post('/messages/direct', [MessagingController::class, 'startDirect'])->name('messages.direct');
        Route::post('/messages/{conversation}', [MessagingController::class, 'send'])->name('messages.send');
    });

    Route::middleware('permission:chat.group.create')->group(function () {
        Route::post('/messages/group', [MessagingController::class, 'createGroup'])->name('messages.group.create');
    });

    // API complémentaires pour l'interactivité du Chat Widget flottant
    Route::get('/chat/mini/{conversation}/messages', [ChatWidgetController::class, 'getMessages'])
        ->middleware('permission:chat.view')
        ->name('chat.mini.messages');

    Route::post('/chat/mini/{conversation}/send', [ChatWidgetController::class, 'sendMessage'])
        ->middleware('permission:chat.send')
        ->name('chat.mini.send');

    // Chat Widget
    Route::get('/chat/mini', [ChatWidgetController::class, 'mini'])
        ->middleware('permission:chat.view')
        ->name('chat.mini');
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