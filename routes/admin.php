<?php

use App\Http\Controllers\Admin\ApplicationsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExtraPermitController;
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
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\DocumentSettingsController;
use App\Http\Controllers\Admin\DocumentTemplateController;
use App\Http\Controllers\Admin\EImzaReleaseController;
use App\Http\Controllers\Admin\FaultController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\ModuleFieldController;
use App\Http\Controllers\Admin\ModuleTemplateController;
use App\Http\Controllers\Admin\ModuleSequenceController;
use App\Http\Controllers\Admin\MakamController;
use App\Http\Controllers\Admin\ProcessController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'license', 'field-team-scope', 'makam-only'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 16.08 - PDF'ler tarayici ayarina bagli olarak "indir" davranisi gosterebiliyordu
    // (Content-Disposition: inline dogru gonderiliyor ama tarayici tercihi ezebiliyor).
    // Iframe tabanli bu viewer, PDF'i HTML sayfasi icine gomer - tarayicinin "PDF her
    // zaman indir" tercihi SADECE ust-seviye navigasyonu etkiler, iframe icini etkilemez.
    Route::get('pdf-viewer', function (\Illuminate\Http\Request $request) {
        $url = (string) $request->query('url', '');

        // FIX (16.08): route() helper'i varsayilan olarak ABSOLUTE URL uretir
        // (orn. http://127.0.0.1:8001/admin/...) - eskiden sadece "/" ile baslayan
        // relative URL'ler kabul ediliyordu, bu yuzden module-document linki 403
        // aliyordu. Simdi absolute URL VERILIRSE once kendi origin'imizle eslesıp
        // esleşmiyorsa 403 (open redirect onlemi), esleşiyorsa path+query'e indirilir.
        if (preg_match('#^https?://#i', $url)) {
            $parsed = parse_url($url);
            $host = $parsed['host'] ?? '';
            $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
            if (($host . $port) !== $request->getHttpHost()) {
                abort(403, 'Gecersiz belge adresi (farkli sunucu).');
            }
            $url = ($parsed['path'] ?? '') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
        }

        $allowedPrefixes = ['/e-imza/', '/storage/', '/admin/applications/'];
        $isRelativeSafe = str_starts_with($url, '/') && ! str_starts_with($url, '//')
            && collect($allowedPrefixes)->contains(fn ($p) => str_starts_with($url, $p));
        if (! $isRelativeSafe) {
            abort(403, 'Gecersiz belge adresi.');
        }

        return view('admin.pdf-viewer', ['url' => $url]);
    })->name('pdf-viewer');

    Route::middleware('license:applications')->group(function () {
        Route::post('applications/data',         [ApplicationsController::class, 'data']          )->name('applications.data');
        Route::post('applications/check-applicant', [ApplicationsController::class, 'checkApplicant'])->name('applications.check-applicant');
        Route::post('applications/metraj-tahmin',    [ApplicationsController::class, 'metrajTahmin']   )->name('applications.metraj-tahmin');
        Route::resource('applications', ApplicationsController::class)->except(['destroy']);
        Route::delete('applications/{application}', [ApplicationsController::class, 'destroy'])->name('applications.destroy');
        Route::post('applications/bulk-destroy',    [ApplicationsController::class, 'bulkDestroy'])->name('applications.bulk-destroy');
        Route::post('applications/{application}/create-additional-permit', [ApplicationsController::class, 'createAdditionalPermit'])->name('applications.create-additional-permit');
        Route::match(['GET', 'POST'], 'applications/{application}/submit', [ApplicationsController::class, 'submit'])->name('applications.submit');
        Route::post('applications/{application}/approve-pre-excavation', [ApplicationsController::class, 'approvePreExcavation'])->name('applications.approve-pre-excavation');
        Route::post('applications/{application}/paraf-step', [ApplicationsController::class, 'parafStep'])->name('applications.paraf-step');
        // 16.08: E-imza öncesi Başkan Yrd. adını sormak için küçük, tek-alanlı endpoint.
        Route::post('applications/{application}/vice-mayor-name', [ApplicationsController::class, 'updateViceMayorName'])->name('applications.update-vice-mayor-name');
        Route::post('applications/{application}/sign-step', [ApplicationsController::class, 'signStep'])->name('applications.sign-step');

        Route::match(['GET', 'POST'], 'applications/{application}/approve-price', [ApplicationsController::class, 'approvePrice'])->name('applications.approve-price');
        Route::post('applications/{application}/complete-field-work', [ApplicationsController::class, 'completeFieldWork'])->name('applications.complete-field-work');
        Route::post('applications/{application}/open-metraj',           [ApplicationsController::class, 'openMetraj']           )->name('applications.open-metraj');
        Route::post('applications/{application}/send-metrage',          [ApplicationsController::class, 'sendMetrageToInstitution'])->name('applications.send-metrage');
        Route::post('applications/{application}/open-odeme-ust-yazi',   [ApplicationsController::class, 'openOdemeUstYazi']      )->name('applications.open-odeme-ust-yazi');
        Route::post('applications/{application}/send-odeme-ust-yazi',   [ApplicationsController::class, 'sendOdemeUstYaziToInstitution'])->name('applications.send-odeme-ust-yazi');
        Route::post('applications/{application}/approve-metrage',       [ApplicationsController::class, 'approveMetrage']       )->name('applications.approve-metrage');
        Route::post('applications/{application}/reject-metrage',        [ApplicationsController::class, 'rejectMetrage']        )->name('applications.reject-metrage');
        Route::post('applications/{application}/open-tahakkuk',         [ApplicationsController::class, 'openTahakkuk']         )->name('applications.open-tahakkuk');
        Route::post('applications/{application}/send-tahakkuk',          [ApplicationsController::class, 'sendTahakkukToInstitution'])->name('applications.send-tahakkuk');
        Route::post('applications/{application}/open-taahhutname',       [ApplicationsController::class, 'openTaahhutname']      )->name('applications.open-taahhutname');
        Route::post('applications/{application}/send-taahhutname',       [ApplicationsController::class, 'sendTaahhutnameToInstitution'])->name('applications.send-taahhutname');
        Route::post('applications/{application}/open-ruhsat',           [ApplicationsController::class, 'openRuhsat']           )->name('applications.open-ruhsat');
        Route::post('applications/{application}/send-ruhsat',           [ApplicationsController::class, 'sendRuhsatToInstitution'])->name('applications.send-ruhsat');
        Route::match(['GET', 'POST'], 'applications/{application}/approve-receipt', [ApplicationsController::class, 'approveReceipt'])->name('applications.approve-receipt');
        Route::post('applications/{application}/reject-receipt', [ApplicationsController::class, 'rejectReceipt'])->name('applications.reject-receipt');
        Route::post('applications/{application}/field-tasks', [ApplicationsController::class, 'transfer'])->name('applications.field-tasks.store');
        Route::post('applications/{application}/transfer', [ApplicationsController::class, 'transferApplication'])->name('applications.transfer');
        Route::post('applications/{application}/transfer-institution', [ApplicationsController::class, 'transferToInstitution'])->name('applications.transfer-institution');
        Route::post('applications/{application}/cancel', [ApplicationsController::class, 'cancel'])->name('applications.cancel');
        Route::post('applications/{application}/receipts', [ApplicationsController::class, 'storeReceipt'])->name('applications.receipts.store');
        Route::get('applications/{application}/license-pdf',      [ApplicationsController::class, 'downloadLicense']       )->name('applications.license-pdf');
        Route::get('applications/{application}/permit-live',     [ApplicationsController::class, 'downloadPermitLive']    )->name('applications.permit-live');
        Route::get('applications/{application}/payment-receipt', [ApplicationsController::class, 'generatePaymentReceipt'])->name('applications.payment-receipt');
        Route::get('applications/{application}/pdf/cover-letter', [ApplicationsController::class, 'downloadCoverLetter'])->name('applications.pdf.cover-letter');
        Route::get('applications/{application}/pdf/pre-permit',   [ApplicationsController::class, 'downloadPrePermit']   )->name('applications.pdf.pre-permit');
        Route::get('applications/{application}/pdf/odeme-ust-yazi', [ApplicationsController::class, 'downloadOdemeUstYazi'])->name('applications.pdf.odeme-ust-yazi');
        Route::get('applications/{application}/pdf/taahhutname',  [ApplicationsController::class, 'downloadTaahhutname'] )->name('applications.pdf.taahhutname');
        Route::get('applications/{application}/pdf/ruhsat',       [ApplicationsController::class, 'downloadRuhsat']     )->name('applications.pdf.ruhsat');
        Route::get('applications/{application}/pdf/metraj',       [ApplicationsController::class, 'downloadMetraj']     )->name('applications.pdf.metraj');
        Route::get('applications/{application}/pdf/tahakkuk',     [ApplicationsController::class, 'downloadTahakkuk']   )->name('applications.pdf.tahakkuk');
        Route::get('applications/{application}/pdf/tahsilat-fisi', [ApplicationsController::class, 'downloadTahsilatFisi'])->name('applications.pdf.tahsilat-fisi');
        Route::put('applications/{application}/save-receipt-info', [ApplicationsController::class, 'saveReceiptInfo']     )->name('applications.save-receipt-info');
        Route::post('applications/{application}/update-surface-lines', [ApplicationsController::class, 'updateSurfaceLines'])->name('applications.update-surface-lines');
        Route::post('applications/{application}/upload-signed-document', [ApplicationsController::class, 'uploadSignedModuleDocument'])->name('applications.upload-signed-document');
        Route::get('applications/{application}/document/{module}', [ApplicationsController::class, 'viewModuleDocument'])->name('applications.module-document');
        Route::get('applications/{application}/edit-document/{documentType}', [DocumentTemplateController::class, 'editApplication'])->name('applications.edit-document');
        Route::post('applications/{application}/edit-document/{documentType}', [DocumentTemplateController::class, 'saveApplication'])->name('applications.edit-document.save');
        Route::delete('applications/{application}/edit-document/{documentType}', [DocumentTemplateController::class, 'destroyApplication'])->name('applications.edit-document.destroy');
        // TAM_WORLD_YAPISI.md Aşama 1 — başvuru özel taslağına Word (.docx) içe aktar
        Route::post('applications/{application}/edit-document/{documentType}/import-word', [DocumentTemplateController::class, 'importWordApplication'])->name('applications.edit-document.import-word');
        // 16.08 5. tur — Taslak Kütüphanesi (başvuruya özel taslak)
        Route::prefix('applications/{application}/edit-document/{documentType}/drafts')->name('applications.edit-document.drafts.')->group(function () {
            Route::get('/',        [DocumentTemplateController::class, 'draftsIndexApplication']  )->name('index');
            Route::post('/',       [DocumentTemplateController::class, 'draftsStoreApplication']  )->name('store');
            Route::get('{draft}',  [DocumentTemplateController::class, 'draftsShowApplication']   )->name('show');
            Route::delete('{draft}', [DocumentTemplateController::class, 'draftsDestroyApplication'])->name('destroy');
        });
        Route::get('applications/{application}/status',          [ApplicationsController::class, 'statusJson']             )->name('applications.status');
        Route::get('api/geocode', [ApplicationsController::class, 'geocodeProxy'])->name('api.geocode');

        Route::get('applications/{application}/extra-permits',          [ExtraPermitController::class, 'index'])->name('extra-permits.index');
        Route::get('applications/{application}/extra-permits/create',   [ExtraPermitController::class, 'create'])->name('extra-permits.create');
        Route::post('applications/{application}/extra-permits',         [ExtraPermitController::class, 'store'])->name('extra-permits.store');
        Route::get('applications/{application}/extra-permits/{extraPermit}', [ExtraPermitController::class, 'show'])->name('extra-permits.show');
        Route::delete('applications/{application}/extra-permits/{extraPermit}', [ExtraPermitController::class, 'destroy'])->name('extra-permits.destroy');

        Route::get('field-tasks/{fieldTask}',        [FieldTaskController::class, 'show']        )->name('field-tasks.show');
        Route::get('field-tasks/{fieldTask}/inspect', [FieldTaskController::class, 'inspect']     )->name('field-tasks.inspect');
        Route::post('field-tasks/{fieldTask}/media',  [FieldTaskController::class, 'addMedia']    )->name('field-tasks.media.store');
        Route::post('field-tasks/{fieldTask}/status', [FieldTaskController::class, 'updateStatus'])->name('field-tasks.status.update');
        Route::post('field-tasks/{fieldTask}/stage',  [FieldTaskController::class, 'updateStage'] )->name('field-tasks.stage.update');
    });

    // ─── Teminat & İadeler + Toplu Arıza (Acil Kazı) ───────────────────────────
    Route::middleware('permission:deposits.view')->group(function () {
        Route::get('deposits',                     [DepositController::class, 'index']        )->name('deposits.index');
        Route::post('deposits/{application}/refund', [DepositController::class, 'refund']     )->name('deposits.refund');
        Route::post('deposits/{application}/update', [DepositController::class, 'update']     )->name('deposits.update');
    });

    Route::middleware('permission:faults.view')->group(function () {
        Route::get('faults',                       [FaultController::class, 'index']         )->name('faults.index');
        Route::post('faults/bulk-tahakkuk',        [FaultController::class, 'bulkTahakkuk']  )->name('faults.bulk-tahakkuk');
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

    Route::middleware(['permission:reports.view', 'license:reports'])->group(function () {
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

    // ─── E-İmza Masaüstü Uygulaması — sürüm yayınlama + indirme (ÇÖZÜM_09 §3) ──
    Route::prefix('e-imza-surum')->name('e-imza-release.')->group(function () {
        // İndirme linki her oturumlu kullanıcıya açık (navbar butonu).
        Route::get('indir', [EImzaReleaseController::class, 'download'])->name('download');

        // Sürüm yükleme/silme yalnızca Süper Admin.
        Route::middleware('role:super-admin')->group(function () {
            Route::get('/',       [EImzaReleaseController::class, 'index']  )->name('index');
            Route::post('/',      [EImzaReleaseController::class, 'store']  )->name('store');
            Route::delete('/',    [EImzaReleaseController::class, 'destroy'])->name('destroy');
        });
    });

    // ─── Evrak & Makam Ayarları (Global Signatory Engine) ─────────────────────
    Route::middleware('permission:document-settings.manage')->prefix('document-settings')->name('document-settings.')->group(function () {
        Route::get('/',               [DocumentSettingsController::class, 'index']  )->name('index');
        Route::post('/',              [DocumentSettingsController::class, 'store'] )->name('store');
        Route::put('/{setting}',      [DocumentSettingsController::class, 'update'])->name('update');
        Route::delete('/{setting}',   [DocumentSettingsController::class, 'destroy'])->name('destroy');
    });

    // ─── EBYS Taslak Motoru — Global Şablon Yönetimi (Word / Excel editör) ────
    Route::middleware('permission:document-templates.manage')->prefix('document-templates')->name('document-templates.')->group(function () {
        Route::get('/',                  [DocumentTemplateController::class, 'index']       )->name('index');
        Route::get('{documentType}/edit', [DocumentTemplateController::class, 'editGlobal'])->name('edit');
        Route::post('{documentType}',     [DocumentTemplateController::class, 'updateGlobal'])->name('update');
        Route::delete('{documentType}/institution', [DocumentTemplateController::class, 'destroyInstitution'])->name('destroy-institution');
        // TAM_WORLD_YAPISI.md Aşama 1 — global/kurum taslağına Word (.docx) içe aktar
        Route::post('{documentType}/import-word', [DocumentTemplateController::class, 'importWordGlobal'])->name('import-word');
        // 16.08 5. tur — Taslak Kütüphanesi (global şablon)
        Route::prefix('{documentType}/drafts')->name('drafts.')->group(function () {
            Route::get('/',        [DocumentTemplateController::class, 'draftsIndexGlobal']  )->name('index');
            Route::post('/',       [DocumentTemplateController::class, 'draftsStoreGlobal']  )->name('store');
            Route::get('{draft}',  [DocumentTemplateController::class, 'draftsShowGlobal']   )->name('show');
            Route::delete('{draft}', [DocumentTemplateController::class, 'draftsDestroyGlobal'])->name('destroy');
        });

        // Kurum bazlı Üst Yazı şablonu (merkezden düzenleme)
        Route::get('institutions/{institution}/cover',     [DocumentTemplateController::class, 'editInstitutionCover']  )->name('edit-institution-cover');
        Route::post('institutions/{institution}/cover',    [DocumentTemplateController::class, 'updateInstitutionCover'])->name('update-institution-cover');
        Route::delete('institutions/{institution}/cover',  [DocumentTemplateController::class, 'destroyInstitutionCover'])->name('destroy-institution-cover');
        Route::post('institutions/{institution}/cover/import-word', [DocumentTemplateController::class, 'importWordInstitutionCover'])->name('import-word-institution-cover');
        // 16.08 5. tur — Taslak Kütüphanesi (kurum Üst Yazı)
        Route::prefix('institutions/{institution}/cover/drafts')->name('drafts-institution-cover.')->group(function () {
            Route::get('/',        [DocumentTemplateController::class, 'draftsIndexInstitutionCover']  )->name('index');
            Route::post('/',       [DocumentTemplateController::class, 'draftsStoreInstitutionCover']  )->name('store');
            Route::get('{draft}',  [DocumentTemplateController::class, 'draftsShowInstitutionCover']   )->name('show');
            Route::delete('{draft}', [DocumentTemplateController::class, 'draftsDestroyInstitutionCover'])->name('destroy');
        });
    });

    // ─── Modül Yönetimi ─────────────────────────────────────────────────────────
    Route::middleware('role_or_permission:super-admin|municipality-admin')->prefix('modules')->name('modules.')->group(function () {
        Route::get('/',                       [ModuleController::class, 'index']  )->name('index');
        Route::get('/create',                 [ModuleController::class, 'create'])->name('create');
        Route::post('/',                      [ModuleController::class, 'store']  )->name('store');
        Route::post('/reorder',               [ModuleController::class, 'reorder'])->name('reorder');
        Route::get('/{module}',              [ModuleController::class, 'show'])->name('show');
        Route::get('/{module}/edit',         [ModuleController::class, 'edit'])->name('edit');
        Route::put('/{module}',              [ModuleController::class, 'update'])->name('update');
        Route::delete('/{module}',            [ModuleController::class, 'destroy'])->name('destroy');

        // Fields
        Route::post('/{module}/fields',            [ModuleFieldController::class, 'store'])->name('fields.store');
        Route::put('/{module}/fields/{field}',     [ModuleFieldController::class, 'update'])->name('fields.update');
        Route::delete('/{module}/fields/{field}',  [ModuleFieldController::class, 'destroy'])->name('fields.destroy');
        Route::post('/{module}/fields/reorder',    [ModuleFieldController::class, 'reorder'])->name('fields.reorder');

        // Templates
        Route::post('/{module}/templates',           [ModuleTemplateController::class, 'store'])->name('templates.store');
        Route::put('/{module}/templates/{template}', [ModuleTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/{module}/templates/{template}', [ModuleTemplateController::class, 'destroy'])->name('templates.destroy');

        // Sequences
        Route::post('/{module}/sequences',              [ModuleSequenceController::class, 'store'])->name('sequences.store');
        Route::put('/{module}/sequences/{sequence}',     [ModuleSequenceController::class, 'update'])->name('sequences.update');
        Route::delete('/{module}/sequences/{sequence}',  [ModuleSequenceController::class, 'destroy'])->name('sequences.destroy');
    });

    // ─── Süreç ve Onay Rotası (Hiyerarşi Yönetim Modülü) — merkez yönetim ─────
    Route::middleware('permission:processes.manage')->prefix('processes')->name('processes.')->group(function () {
        Route::get('/',                        [ProcessController::class, 'index']            )->name('index');
        Route::post('/',                       [ProcessController::class, 'storeDefinition']   )->name('store-definition');
        Route::post('/steps',                  [ProcessController::class, 'storeStep']         )->name('store-step');
        Route::put('/steps/{step}',            [ProcessController::class, 'updateStep']        )->name('update-step');
        Route::delete('/steps/{step}',         [ProcessController::class, 'destroyStep']       )->name('destroy-step');
        Route::post('/steps/{step}/reorder/{direction}', [ProcessController::class, 'reorderStep'])->name('reorder-step');
        Route::post('/{process}/set-default',  [ProcessController::class, 'setDefault']        )->name('set-default');
        Route::post('/{process}/toggle-active',[ProcessController::class, 'toggleActive']      )->name('toggle-active');
        Route::match(['put', 'patch'], '/{process}', [ProcessController::class, 'updateDefinition'])->name('update-definition');
        Route::delete('/{process}',            [ProcessController::class, 'destroyDefinition'])->name('destroy-definition');

        // Blueprint Canvas
        Route::get('/{process}/blueprint',     [ProcessController::class, 'blueprint']        )->name('blueprint');
        Route::post('/{process}/save-canvas', [ProcessController::class, 'saveCanvas']       )->name('save-canvas');
        Route::post('/{process}/publish',      [ProcessController::class, 'publish']           )->name('publish');
    });

    // ─── Makam Masası (Başkan / Karar Yeri) ──────────────────────────────────
    Route::middleware('permission:makam.view')->prefix('makam')->name('makam.')->group(function () {
        Route::get('/',                     [MakamController::class, 'index'])->name('index');
        Route::get('/{application}',        [MakamController::class, 'show']  )->name('show');
        Route::post('/{application}/onayla',[MakamController::class, 'onayla'])->name('onayla');
    });

    // ─── PRO Modüller ──────────────────────────────────────────────────────────
    Route::middleware('can:pro.work_orders')->group(function () {
        Route::get('work-orders',                    [WorkOrderController::class,  'index']    )->name('work-orders.index');
        Route::post('work-orders/data',              [WorkOrderController::class,  'data']     )->name('work-orders.data');
        Route::get('work-orders/export/csv',         [WorkOrderController::class,  'exportCsv'])->name('work-orders.export-csv');
        Route::get('work-orders/export/pdf',         [WorkOrderController::class,  'exportPdf'])->name('work-orders.export-pdf');
    });
    Route::middleware('can:pro.field_reports')->group(function () {
        Route::get('field-reports-pro',              [FieldReportController::class,'index']    )->name('field-reports-pro.index');
        Route::get('field-reports-pro/export/csv',   [FieldReportController::class,'exportCsv'])->name('field-reports-pro.export-csv');
        Route::get('field-reports-pro/export/pdf',   [FieldReportController::class,'exportPdf'])->name('field-reports-pro.export-pdf');
    });
    Route::middleware('can:pro.evrak_tevdi')->group(function () {
        Route::get('e-document', fn () => view('admin.e-document.index'))->name('e-document.index');
    });

    // ─── Canlı Saha İzleme PRO ────────────────────────────────────────────────
    Route::middleware('can:pro.live_map')->group(function () {
        Route::get( 'live-map-pro',          [LiveMapController::class, 'index']         )->name('live-map-pro.index');
        Route::get( 'live-map-pro/data',     [LiveMapController::class, 'liveData']      )->name('live-map-pro.data');
        Route::post('field/checkin',         [LiveMapController::class, 'checkIn']       )->name('field.checkin');
        Route::post('field/location',        [LiveMapController::class, 'updateLocation'])->name('field.location');
    });

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
