<?php

use App\Http\Controllers\ParapheurController;
use App\Http\Controllers\CourrierCommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CourrierController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\ChatWidgetController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BureauController as AdminBureauController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {



    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Parapheur électronique
    Route::get('/parapheur', [ParapheurController::class, 'index'])->name('parapheur.index');
    Route::post('/parapheur/{visa}/approve', [ParapheurController::class, 'approve'])->name('parapheur.approve');
    Route::post('/parapheur/{visa}/reject', [ParapheurController::class, 'reject'])->name('parapheur.reject');

    // Notifications (in-app)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread_count');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

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
        Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::resource('bureaux', AdminBureauController::class)
            ->except(['show'])
            ->parameters(['bureaux' => 'bureau']);
    });

    // Alias public demandé pour la recherche avancée
    Route::get('/search', [SearchController::class, 'index'])
        ->middleware('permission:courrier.view')
        ->name('search');

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

        Route::post('/{courrier}/comments', [CourrierCommentController::class, 'store'])
            ->middleware('permission:courrier.view')
            ->name('comments.store');

        Route::post('/{courrier}/parapheur', [CourrierController::class, 'submitToParapheur'])
            ->middleware('permission:courrier.view')
            ->name('parapheur.submit');

        Route::post('/{courrier}/links', [CourrierController::class, 'linkCourrier'])
            ->middleware('permission:courrier.view')
            ->name('links.store');

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
    Route::middleware('permission:chat.send')->group(function () {
        Route::post('/messages/direct', [MessagingController::class, 'startDirect'])->name('messages.direct');
    });

    Route::middleware('permission:chat.group.create')->group(function () {
        Route::post('/messages/group', [MessagingController::class, 'createGroup'])->name('messages.group.create');
    });

    Route::middleware('permission:chat.view')->group(function () {
        Route::get('/messages', [MessagingController::class, 'index'])->name('messages.index');
        Route::get('/messages/{conversation}', [MessagingController::class, 'show'])->name('messages.show');
    });

    Route::middleware('permission:chat.send')->group(function () {
        Route::post('/messages/{conversation}/members', [MessagingController::class, 'addMembers'])
            ->name('messages.members.add');
        Route::delete('/messages/{conversation}/members/{user}', [MessagingController::class, 'removeMember'])
            ->name('messages.members.remove');
        Route::post('/messages/{conversation}/leave', [MessagingController::class, 'leave'])
            ->name('messages.leave');
        Route::patch('/messages/{conversation}/rename', [MessagingController::class, 'rename'])
            ->name('messages.rename');
        Route::delete('/messages/{conversation}/messages/{message}', [MessagingController::class, 'destroyMessage'])
            ->name('messages.destroy');
        Route::post('/messages/{conversation}', [MessagingController::class, 'send'])->name('messages.send');
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

Route::get('/lang/{locale}', [LanguageController::class, 'switch'])
    ->whereIn('locale', ['fr', 'en'])
    ->name('lang.switch');

// Compatibilité pages login / welcome (formulaires POST)
Route::post('/locale', function (Request $request) {
    $locale = (string) $request->input('locale', config('app.fallback_locale', 'fr'));

    if (! in_array($locale, ['fr', 'en'], true)) {
        $locale = config('app.fallback_locale', 'fr');
    }

    return app(LanguageController::class)->switch($locale);
})->name('locale.set');

require __DIR__ . '/auth.php';