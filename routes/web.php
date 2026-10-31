<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Crm\ClientController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UniversalImportController;
use Illuminate\Support\Facades\Route;

// ─── Auth Routes ──────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/auth/azure/redirect', [AuthController::class, 'redirectToAzure'])->name('azure.redirect');
    Route::get('/auth/azure/callback', [AuthController::class, 'handleAzureCallback'])->name('azure.callback');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Password reset
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/share/document/{token}', [DocumentController::class, 'share'])->name('documents.share');

// ─── Dashboard & App Routes ────────────────────────────────────────────────────
Route::middleware(['auth', 'user.active'])->group(function () {
    // Dashboard
    Route::middleware('permission:dashboard,read')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', function () {
            return view('pages.dashboard.analytics');
        });
    });

       // CRM / Leads
    Route::middleware('permission:leads,read')->group(function () {
        Route::get('/crm/pipeline', [LeadController::class, 'index'])->name('crm.pipeline');
        Route::get('/crm/pipeline/leads-data', [LeadController::class, 'listLeads'])->name('crm.pipeline.leads-data');
        Route::get('/crm/pipeline/stage', [LeadController::class, 'stageLeads'])->name('crm.pipeline.stage');
        Route::get('/crm/leads/{lead}', [LeadController::class, 'show'])->name('crm.leads.show');
        Route::middleware('permission:leads,write')->group(function () {
            Route::get('/crm/create', [LeadController::class, 'create'])->name('crm.create');
            Route::post('/crm/leads', [LeadController::class, 'store'])->name('crm.store');
            Route::match(['put', 'patch'], '/crm/leads/{lead}', [LeadController::class, 'update'])->name('crm.leads.update');
            Route::delete('/crm/leads/{lead}', [LeadController::class, 'destroy'])->name('crm.leads.destroy');
            Route::post('/crm/leads/import', [LeadController::class, 'import'])->name('crm.import');
            Route::get('/import/templates/leads', [UniversalImportController::class, 'downloadTemplate'])
                ->defaults('destination', 'leads')
                ->name('import.templates.leads');
            Route::post('/crm/leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('crm.leads.notes.store');
            Route::post('/crm/leads/{lead}/convert', [LeadController::class, 'convert'])->name('crm.convert');
            Route::patch('/crm/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('crm.updateStatus');
        });
    });

    Route::middleware('permission:reports,read')->group(function () {
        Route::get('/crm/reports', [ReportController::class, 'index'])->name('crm.reports');
        Route::post('/crm/reports/data', [ReportController::class, 'data'])->name('crm.reports.data');
    });

    // Universal Bulk Import
    Route::post('/import/upload', [UniversalImportController::class, 'upload'])->name('import.upload');
    Route::post('/import/headers', [UniversalImportController::class, 'headers'])->name('import.headers');
    Route::post('/import/preview', [UniversalImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/process', [UniversalImportController::class, 'process'])->name('import.process');
    Route::get('/import/status/{uuid}', [UniversalImportController::class, 'status'])->name('import.status');
    Route::get('/import/status/{uuid}/errors.csv', [UniversalImportController::class, 'downloadErrors'])->name('import.errors.download');

    // Clients: each status page follows its own sidebar permission.
    Route::middleware('permission:clients,read')->group(function () {
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('/clients/data', [ClientController::class, 'allClientsData'])->name('clients.all.data');
    });
    Route::get('/clients/{client}/profile', [ClientController::class, 'profile'])
        ->middleware('permission:clients_profile,read')
        ->name('clients.profile');
    Route::get('/clients/requests', [ClientController::class, 'clientRequests'])
        ->middleware('permission:clients_claims,read')
        ->name('clients.requests');
    Route::post('/clients/requests/data', [ClientController::class, 'clientRequestsData'])
        ->middleware('permission:clients_claims,read')
        ->name('clients.requests.data');
    Route::patch('/clients/requests/{serviceRequest}', [ClientController::class, 'updateClientRequest'])
        ->middleware('permission:clients_claims,write')
        ->name('clients.requests.update');
    Route::post('/clients/requests/{serviceRequest}/resolve', [ClientController::class, 'resolveClientRequest'])
        ->middleware('permission:clients_claims,write')
        ->name('clients.requests.resolve');
    Route::post('/clients/requests/{serviceRequest}/move-to-inforce', [ClientController::class, 'moveClientRequestToInforce'])
        ->middleware('permission:clients_claims,write')
        ->name('clients.requests.move-to-inforce');
    foreach ([
        'inforce' => 'clients_inforce',
        'inactive' => 'clients_inactive',
        'claims' => 'clients_claims',
        'cancellation' => 'clients_cancellation',
        'npw-deferred' => 'clients_npw_deferred',
    ] as $status => $module) {
        Route::get('/clients/'.$status, [ClientController::class, 'index'])
            ->middleware('permission:'.$module.',read')
            ->defaults('status', $status)
            ->name('clients.'.$status);

        // AJAX data endpoint for DataTables
        $dataMethodMap = [
            'inforce' => 'inforceClientsData',
            'inactive' => 'inactiveClientsData',
            'claims' => 'claimsClientsData',
            'cancellation' => 'cancellationClientsData',
            'npw-deferred' => 'npwDeferredClientsData',
        ];
        Route::post('/clients/'.$status.'/data', [ClientController::class, $dataMethodMap[$status]])
            ->middleware('permission:'.$module.',read')
            ->name('clients.'.$status.'.data');

        Route::middleware('permission:'.$module.',write')->group(function () use ($status) {
            Route::post('/clients/'.$status.'/{client}/claim', [ClientController::class, 'markClientStageAsClaim'])
                ->defaults('stage', $status)
                ->name('clients.'.$status.'.claim');
            Route::post('/clients/'.$status.'/{client}/inactive', [ClientController::class, 'markAsInactive'])
                ->name('clients.'.$status.'.inactive');
            Route::post('/clients/'.$status.'/{client}/npw-deferred', [ClientController::class, 'markAsNpwDeferred'])
                ->name('clients.'.$status.'.npw_deferred');
        });
    }

    Route::middleware('permission:clients,write')->group(function () {
        Route::match(['put', 'patch'], '/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::post('/clients/all/{client}/claim', [ClientController::class, 'markClientStageAsClaim'])
            ->defaults('stage', 'all')->name('clients.all.claim');
        Route::post('/clients/all/{client}/inactive', [ClientController::class, 'markAsInactive'])->name('clients.all.inactive');
        Route::post('/clients/all/{client}/npw-deferred', [ClientController::class, 'markAsNpwDeferred'])->name('clients.all.npw_deferred');
    });
    Route::middleware('permission:clients_inforce,write')->group(function () {
        Route::post('/clients/inforce/{client}/requests', [ClientController::class, 'storeLoginClientRequest'])->name('clients.inforce.requests.store');
        Route::post('/clients/inforce/{client}/cancellation', [ClientController::class, 'markInforceAsCancellation'])->name('clients.inforce.cancellation');
        Route::delete('/clients/inforce/{client}', [ClientController::class, 'destroyInforceClient'])->name('clients.inforce.destroy');
    });
    Route::middleware('permission:clients_inactive,write')->group(function () {
        Route::post('/clients/inactive/{client}/inforce', [ClientController::class, 'markInactiveAsInforce'])->name('clients.inactive.inforce');
    });
    Route::middleware('permission:clients_claims,write')->group(function () {
        Route::post('/clients/claims/{client}/inforce', [ClientController::class, 'markClaimsAsInforce'])->name('clients.claims.inforce');
        Route::post('/clients/claims/{client}/cancellation', [ClientController::class, 'markClaimsAsCancellation'])->name('clients.claims.cancellation');
    });
    Route::middleware('permission:clients_cancellation,write')->group(function () {
        Route::post('/clients/cancellation/{client}/inforce', [ClientController::class, 'markCancellationAsInforce'])->name('clients.cancellation.inforce');
        Route::post('/clients/cancellation/{client}/inactive', [ClientController::class, 'markCancellationAsInactive'])->name('clients.cancellation.inactive');
    });
    Route::middleware('permission:clients_npw_deferred,write')->group(function () {
        Route::post('/clients/npw-deferred/{client}/inforce', [ClientController::class, 'markNpwAsInforce'])->name('clients.npw_deferred.inforce');
        Route::post('/clients/npw-deferred/{client}/cancellation', [ClientController::class, 'markNpwAsCancellation'])->name('clients.npw_deferred.cancellation');
    });

    // Login Clients CRUD
    Route::middleware('permission:clients_login,read')->group(function () {
        Route::get('/auth/clients-login', [ClientController::class, 'loginClients'])->name('clients.login');
        Route::post('/auth/clients-login/data', [ClientController::class, 'loginClientsData'])->name('clients.login.data');
        Route::middleware('permission:clients_login,write')->group(function () {
            Route::get('/import/templates/login-clients', [UniversalImportController::class, 'downloadTemplate'])
                ->defaults('destination', 'login_client')
                ->name('import.templates.login-clients');
            Route::post('/clients/login', [ClientController::class, 'storeLoginClient'])->name('clients.login.store');
            Route::post('/clients/login/{client}/requests', [ClientController::class, 'storeLoginClientRequest'])->name('clients.login.requests.store');
            Route::patch('/clients/login/{client}', [ClientController::class, 'updateLoginClient'])->name('clients.login.update');
            Route::post('/clients/login/{client}/move-to-inforce', [ClientController::class, 'moveLoginClientToInforce'])->name('clients.login.move-to-inforce');
            Route::post('/clients/login/{client}/inforce', [ClientController::class, 'markAsInforce'])->name('clients.login.inforce');
            Route::post('/clients/login/{client}/claim', [ClientController::class, 'markAsClaim'])->name('clients.login.claim');
            Route::post('/clients/login/{client}/cancellation', [ClientController::class, 'markAsCancellation'])->name('clients.login.cancellation');
            Route::post('/clients/login/{client}/npw-deferred', [ClientController::class, 'markAsNpwDeferred'])->name('clients.login.npw_deferred');
            Route::post('/clients/login/{client}/inactive', [ClientController::class, 'markAsInactive'])->name('clients.login.inactive');
            Route::delete('/clients/login/{client}', [ClientController::class, 'destroyLoginClient'])->name('clients.login.destroy');
        });
    });

    // Utilities
    Route::middleware('permission:calendar,read')->group(function () {
        Route::get('/utilities/calendar', [AppointmentController::class, 'index'])->name('calendar.index');
        Route::middleware('permission:calendar,write')->group(function () {
            Route::post('/utilities/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
            Route::delete('/utilities/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
            Route::post('/utilities/appointments/{appointment}/remind', [AppointmentController::class, 'sendReminder'])->name('appointments.remind');
        });
    });
    Route::middleware('permission:tasks,read')->group(function () {
        Route::get('/utilities/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::get('/utilities/tasks/clients/search', [TaskController::class, 'clientSearch'])->name('tasks.clients.search');
        Route::middleware('permission:tasks,write')->group(function () {
            Route::post('/utilities/tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::patch('/utilities/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
            Route::post('/utilities/tasks/{task}/notes', [TaskController::class, 'storeNote'])->name('tasks.notes.store');
            Route::delete('/utilities/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        });
    });
    Route::middleware('permission:documents,read')->group(function () {
        Route::get('/utilities/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/utilities/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::middleware('permission:documents,write')->group(function () {
            Route::post('/document-upload', [DocumentController::class, 'store'])->name('documents.store');
            Route::post('/document-upload-test', [DocumentController::class, 'testUpload'])->name('documents.test');
            Route::delete('/utilities/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        });
    });

    // ─── Access Control ──────────────────────────────────────────────────────
    Route::middleware('permission:access,read')->group(function () {
        Route::get('/settings/access', [UserController::class, 'index'])->name('settings.access');
        Route::get('/settings/dropdowns/{group?}', [\App\Http\Controllers\Settings\DropdownValueController::class, 'index'])->name('settings.dropdowns');

        Route::middleware('permission:access,write')->group(function () {
            Route::post('/settings/users', [UserController::class, 'store'])->name('users.store');
            Route::patch('/settings/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/settings/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
            Route::delete('/settings/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::post('/settings/roles', [UserController::class, 'storeRole'])->name('roles.store');
            Route::patch('/settings/roles/{role}', [UserController::class, 'updateRole'])->name('roles.update');
            Route::delete('/settings/roles/{role}', [UserController::class, 'destroyRole'])->name('roles.destroy');
            Route::post('/settings/dropdowns', [\App\Http\Controllers\Settings\DropdownValueController::class, 'store'])->name('settings.dropdowns.store');
            Route::patch('/settings/dropdowns/{lookupValue}', [\App\Http\Controllers\Settings\DropdownValueController::class, 'update'])->name('settings.dropdowns.update');
            Route::delete('/settings/dropdowns/{lookupValue}', [\App\Http\Controllers\Settings\DropdownValueController::class, 'destroy'])->name('settings.dropdowns.destroy');
        });
    });

    // ─── Notifications ────────────────────────────────────────────────────────────
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
});

// Auth Static Views
Route::get('/auth/login', function () {
    return view('pages.auth.login');
});
Route::get('/auth/forgot-password', function () {
    return redirect()->route('password.request');
});
Route::get('/auth/reset-password', function () {
    return redirect()->route('password.request');
});
