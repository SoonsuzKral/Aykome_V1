<?php

use App\Http\Controllers\Admin\ApplicationsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FieldTaskController;
use App\Http\Controllers\Admin\FieldReportController;
use App\Http\Controllers\Admin\InstitutionController;
use App\Http\Controllers\Admin\LicenseController;
use App\Http\Controllers\Admin\MapMonitorController;
use App\Http\Controllers\Admin\MyTasksController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PreExcavationPermitSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LiveMapController;
use App\Http\Controllers\Admin\SurfaceTypeController;
use App\Http\Controllers\Admin\WorkOrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'license', 'field-team-scope'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('license:applications')->group(function () {
        Route::post('applications/data',         [ApplicationsController::class, 'data']          )->name('applications.data');
        Route::post('applications/check-applicant', [ApplicationsController::class, 'checkApplicant'])->name('applications.check-applicant');
        Route::resource('applications', ApplicationsController::class)->except(['destroy']);
        Route::delete('applications/{application}', [ApplicationsController::class, 'destroy'])->name('applications.destroy');
        Route::post('applications/bulk-destroy',    [ApplicationsController::class, 'bulkDestroy'])->name('applications.bulk-destroy');
        Route::post('applications/{application}/submit', [ApplicationsController::class, 'submit'])->name('applications.submit');
        Route::post('applications/{application}/approve-pre-excavation', [ApplicationsController::class, 'approvePreExcavation'])->name('applications.approve-pre-excavation');
        Route::get('applications/{application}/pre-excavation-permit', [ApplicationsController::class, 'downloadPreExcavationPermit'])->name('applications.pre-excavation-permit');
        Route::post('applications/{application}/approve-price', [ApplicationsController::class, 'approvePrice'])->name('applications.approve-price');
        Route::post('applications/{application}/approve-receipt', [ApplicationsController::class, 'approveReceipt'])->name('applications.approve-receipt');
        Route::post('applications/{application}/reject-receipt', [ApplicationsController::class, 'rejectReceipt'])->name('applications.reject-receipt');
        Route::post('applications/{application}/field-tasks', [ApplicationsController::class, 'transfer'])->name('applications.field-tasks.store');
        Route::post('applications/{application}/receipts', [ApplicationsController::class, 'storeReceipt'])->name('applications.receipts.store');
        Route::get('applications/{application}/license-pdf',      [ApplicationsController::class, 'downloadLicense']       )->name('applications.license-pdf');
        Route::get('applications/{application}/permit-live',     [ApplicationsController::class, 'downloadPermitLive']    )->name('applications.permit-live');
        Route::get('applications/{application}/payment-receipt', [ApplicationsController::class, 'generatePaymentReceipt'])->name('applications.payment-receipt');
        Route::get('applications/{application}/status',          [ApplicationsController::class, 'statusJson']             )->name('applications.status');

        Route::get('field-tasks/{fieldTask}',        [FieldTaskController::class, 'show']        )->name('field-tasks.show');
        Route::get('field-tasks/{fieldTask}/inspect', [FieldTaskController::class, 'inspect']     )->name('field-tasks.inspect');
        Route::post('field-tasks/{fieldTask}/media',  [FieldTaskController::class, 'addMedia']    )->name('field-tasks.media.store');
        Route::post('field-tasks/{fieldTask}/status', [FieldTaskController::class, 'updateStatus'])->name('field-tasks.status.update');
        Route::post('field-tasks/{fieldTask}/stage',  [FieldTaskController::class, 'updateStage'] )->name('field-tasks.stage.update');
    });

    // ─── Zemin Tipleri ────────────────────────────────────────────────────────
    Route::middleware('permission:surface_types.manage')->group(function () {
        Route::get('surface-types',                    [SurfaceTypeController::class, 'index']  )->name('surface-types.index');
        Route::post('surface-types',                   [SurfaceTypeController::class, 'store']  )->name('surface-types.store');
        Route::put('surface-types/{surfaceType}',      [SurfaceTypeController::class, 'update'] )->name('surface-types.update');
        Route::delete('surface-types/{surfaceType}',   [SurfaceTypeController::class, 'destroy'])->name('surface-types.destroy');
    });

    // ─── Kurumlar & Firmalar ───────────────────────────────────────────────────
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('institutions',              [InstitutionController::class, 'index']   )->name('institutions.index');
        Route::post('institutions/data',        [InstitutionController::class, 'data']    )->name('institutions.data');
        Route::post('institutions',             [InstitutionController::class, 'store']   )->name('institutions.store');
        Route::get('institutions/{institution}/edit-json',    [InstitutionController::class, 'editJson'] )->name('institutions.edit-json');
        Route::put('institutions/{institution}',             [InstitutionController::class, 'update']   )->name('institutions.update');
        Route::delete('institutions/{institution}',          [InstitutionController::class, 'destroy']  )->name('institutions.destroy');
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::post('users/data', [UserController::class, 'data'])->name('users.data');
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class)->except(['show', 'destroy']);
    });

    Route::middleware('permission:licenses.manage')->group(function () {
        Route::get('licenses/create', [LicenseController::class, 'create'])->name('licenses.create');
        Route::post('licenses', [LicenseController::class, 'store'])->name('licenses.store');
        Route::get('licenses/{license}/edit', [LicenseController::class, 'edit'])->name('licenses.edit');
        Route::put('licenses/{license}', [LicenseController::class, 'update'])->name('licenses.update');
        Route::post('licenses/{license}/renew', [LicenseController::class, 'renew'])->name('licenses.renew');
        Route::post('licenses/{license}/kill',  [LicenseController::class, 'kill'] )->name('licenses.kill');
        Route::get('licenses', [LicenseController::class, 'index'])->name('licenses.index');
    });

    Route::middleware(['permission:applications.view', 'license:map'])->group(function () {
        Route::get('map', [MapMonitorController::class, 'index'])->name('map.index');
        Route::post('map/{application}/drawing', [MapMonitorController::class, 'storeDrawing'])->name('map.drawing.store');
    });

    Route::middleware(['permission:applications.view', 'license:reports'])->group(function () {
        Route::get('reports',            [ReportController::class, 'index']    )->name('reports.index');
        Route::get('reports/advanced',   [ReportController::class, 'advanced'] )->name('reports.advanced');
        Route::post('reports/data',      [ReportController::class, 'data']     )->name('reports.data');
        Route::match(['get','post'], 'reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::match(['get','post'], 'reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');
    });

    // ─── Saha Personeli: Bana Atanan Görevler ─────────────────────────────────
    Route::middleware('permission:field.tasks_view')->group(function () {
        Route::get('my-tasks', [MyTasksController::class, 'index'])->name('my-tasks.index');
    });

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    // ─── Super Admin: Audit Log + Settings ────────────────────────────────────
    Route::middleware('role:super-admin')->group(function () {
        Route::get('logs',      [AuditLogController::class, 'index'])->name('logs.index');
        Route::get('logs/data', [AuditLogController::class, 'data'] )->name('logs.data');

        Route::get('settings/permit',  [SettingsController::class, 'permit']       )->name('settings.permit');
        Route::put('settings/permit',  [SettingsController::class, 'updatePermit'] )->name('settings.permit.update');

        Route::get('settings/pre-excavation-permit',  [PreExcavationPermitSettingController::class, 'edit']  )->name('settings.pre-excavation-permit');
        Route::put('settings/pre-excavation-permit',  [PreExcavationPermitSettingController::class, 'update'])->name('settings.pre-excavation-permit.update');
    });

    // ─── PRO Modüller ──────────────────────────────────────────────────────────
    Route::get('work-orders',                    [WorkOrderController::class,  'index']    )->name('work-orders.index');
    Route::post('work-orders/data',              [WorkOrderController::class,  'data']     )->name('work-orders.data');
    Route::get('work-orders/export/csv',         [WorkOrderController::class,  'exportCsv'])->name('work-orders.export-csv');
    Route::get('work-orders/export/pdf',         [WorkOrderController::class,  'exportPdf'])->name('work-orders.export-pdf');
    Route::get('field-reports-pro',              [FieldReportController::class,'index']    )->name('field-reports-pro.index');
    Route::get('field-reports-pro/export/csv',   [FieldReportController::class,'exportCsv'])->name('field-reports-pro.export-csv');
    Route::get('field-reports-pro/export/pdf',   [FieldReportController::class,'exportPdf'])->name('field-reports-pro.export-pdf');
    Route::middleware('can:pro.evrak_tevdi')->get('e-document', fn () => view('admin.e-document.index'))->name('e-document.index');

    // ─── Canlı Saha İzleme PRO ────────────────────────────────────────────────
    Route::get( 'live-map-pro',          [LiveMapController::class, 'index']         )->name('live-map-pro.index');
    Route::get( 'live-map-pro/data',     [LiveMapController::class, 'liveData']      )->name('live-map-pro.data');
    Route::post('field/checkin',         [LiveMapController::class, 'checkIn']       )->name('field.checkin');
    Route::post('field/location',        [LiveMapController::class, 'updateLocation'])->name('field.location');

    // ─── Oracle Veritabani Yonetimi (Super Admin) ──────────────────────────
    Route::middleware('role:super-admin')->prefix('oracle')->name('oracle.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\OracleBrowserController::class, 'index'])->name('index');
        Route::post('/query', [\App\Http\Controllers\Admin\OracleBrowserController::class, 'query'])->name('query');
        Route::post('/table-data', [\App\Http\Controllers\Admin\OracleBrowserController::class, 'tableData'])->name('table-data');
        Route::post('/migrate', [\App\Http\Controllers\Admin\OracleBrowserController::class, 'migrate'])->name('migrate');
    });

    // Isolated map test page (no auth/perm middleware check for testing)
    Route::get('map-test', function () {
        return view('admin.map.test', [
            'googleMapsApiKey' => config('services.google_maps.api_key') ?: config('aykome.google_maps_api_key'),
        ]);
    })->name('map.test');
});
