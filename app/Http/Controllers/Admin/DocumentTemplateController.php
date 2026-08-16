<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationSurfaceArea;
use App\Services\DocumentTemplateService;
use App\Services\PricingService;
use App\Enums\ApplicationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    protected function guardAccess(): void
    {
        $user = auth()->user();

        // ROBUST İZOLASYON: Şablon/Taslak Yönetimi yalnızca Merkez Belediye yönetimine
        // (Super Admin + municipality-* rolleri) açıktır. Alt kurum personeli
        // (institution_id dolu) bu modüle ASLA giremez — şablon bozma riski bloke edilir.
        if (! $user->isMunicipalityPersonel()) {
            abort(403, 'Şablon yönetimi yalnızca belediye merkez yönetimine açıktır.');
        }
    }

    /** Alt kurum kullanıcısı mı? (yalnızca Üst Yazı ve kendi kurumu) */
    protected function isInstitutionScope(): bool
    {
        $user = auth()->user();

        return ! $user->isMunicipalityPersonel() && $user->institution_id;
    }

    /** 📝 Taslak / Şablon Yönetimi — Ana Üst Yazı (master) + Kurum üst yazı şablonları. */
    public function index(): View
    {
        $this->guardAccess();

        $user = auth()->user();
        $institutionScope = $this->isInstitutionScope();

        $types = [];
        foreach (DocumentTemplateService::TYPES as $key => $t) {
            // GÖREV 3.1: Alt kurum "Ön Kazı Şablonu" kartını GÖRMEZ — Ön Kazı belediyeye aittir.
            // Diğer tüm şablonlar açıktır.
            if ($institutionScope && $key === 'on_kazi') {
                continue;
            }

            // GÖREV 3a: "Tahsilat Makbuzu (Word)" şablon kartı index'ten tamamen kaldırıldı;
            // yalnızca fiş (Tahsilat Fişi) listelenir. (makbuz e-imza üretiminde kalır, kart gizlenir.)
            if ($key === 'makbuz') {
                continue;
            }

            $content = $institutionScope
                ? DocumentTemplateService::institutionContent($user->institution_id, $key)
                : DocumentTemplateService::globalContent($key);

            $types[] = [
                'key' => $key,
                'label' => $t['full'],
                'desc' => $t['desc'],
                'editor' => $t['editor'],
                'icon' => $t['icon'],
                'hasTemplate' => $content !== null,
                'editUrl' => route('admin.document-templates.edit', $key),
                'scope' => $institutionScope ? 'institution' : 'global',
            ];
        }

        // KURUM BAZLI ÜST YAZI ŞABLONLARI (merkez yönetim): tüm alt kurumların
        // ayrı Üst Yazı şablonları listelenir. Kurum şablonu otomatik seed edilir
        // (InstitutionController::store) — burada yalnızca durumu gösterilir.
        $institutions = collect();
        if (! $institutionScope) {
            $institutions = \App\Models\Institution::query()
                ->where('is_municipality', false)
                ->orderBy('name')
                ->get()
                ->map(fn ($inst) => [
                    'id' => $inst->id,
                    'name' => $inst->name,
                    'color_code' => $inst->color_code,
                    'hasTemplate' => DocumentTemplateService::institutionContent($inst->id, 'cover_letter') !== null,
                ]);
        }

        return view('admin.document-templates.index', [
            'types' => $types,
            'institutionScope' => $institutionScope,
            'institution' => $institutionScope ? $user->institution : null,
            'institutions' => $institutions,
        ]);
    }

    /** Global (master) şablonu düzenle — tam ekran Word/Excel editörü. */
    public function editGlobal(string $documentType): View
    {
        $this->guardAccess();
        $t = DocumentTemplateService::type($documentType) ?: abort(404, 'Bilinmeyen belge tipi.');

        $institutionScope = $this->isInstitutionScope();
        // GÖREV 3.1: Alt kurum Ön Kazı şablonunu düzenleyemez (belediye yetkisi).
        if ($institutionScope && $documentType === 'on_kazi') {
            abort(403, 'Ön Kazı şablonu belediye yetkisindedir.');
        }

        $src = DocumentTemplateService::editorSource($documentType, null, $institutionScope ? (int) auth()->user()->institution_id : null);

        return $this->editorView([
            'docType' => $documentType,
            'docLabel' => $t['full'],
            'scope' => $institutionScope ? 'institution' : 'global',
            'applicationId' => null,
            'editorType' => $src['editor'],
            'editorGridType' => self::gridType($documentType),
            'initialContent' => $src['content'],
            'docCss' => $src['css'],
            'saveUrl' => route('admin.document-templates.update', $documentType),
            'resetUrl' => $institutionScope ? route('admin.document-templates.destroy-institution', $documentType) : null,
            'importWordUrl' => route('admin.document-templates.import-word', $documentType),
            'draftsUrl' => route('admin.document-templates.drafts.index', $documentType),
            'backUrl' => route('admin.document-templates.index'),
            'title' => $t['full'],
        ]);
    }

    public function updateGlobal(Request $request, string $documentType): JsonResponse
    {
        $this->guardAccess();
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        $user = auth()->user();
        $content = (string) $request->input('content_data');

        if ($this->isInstitutionScope()) {
            // GÖREV 3.1: Alt kurum Ön Kazı şablonunu kaydedemez (belediye yetkisi).
            if ($documentType === 'on_kazi') {
                abort(403, 'Ön Kazı şablonu belediye yetkisindedir.');
            }
            DocumentTemplateService::saveInstitution((int) $user->institution_id, $documentType, $content);
        } else {
            DocumentTemplateService::saveGlobal($documentType, $content);
        }

        return response()->json(['ok' => true]);
    }

    /** Kuruma özel şablonu sil (yalnızca alt kurum kendi üst yazısı için). */
    public function destroyInstitution(string $documentType)
    {
        $this->guardAccess();
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        $user = auth()->user();
        if ($this->isInstitutionScope()) {
            // GÖREV 3.1: Alt kurum Ön Kazı şablonunu silemez (belediye yetkisi).
            if ($documentType === 'on_kazi') {
                abort(403);
            }
            DocumentTemplateService::deleteInstitution((int) $user->institution_id, $documentType);
        }

        return back()->with('success', 'Kurum şablonu kaldırıldı. Varsayılan şablona dönüldü.');
    }

    /** Kurum Üst Yazı şablonunu düzenle (yalnızca belediye merkez yönetimi). */
    public function editInstitutionCover(\App\Models\Institution $institution): View
    {
        $this->guardAccess();
        abort_unless(! (bool) $institution->is_municipality, 403, 'Yalnızca alt kurum üst yazı şablonları düzenlenebilir.');

        $src = DocumentTemplateService::editorSource('cover_letter', null, $institution->id);

        return $this->editorView([
            'docType' => 'cover_letter',
            'docLabel' => 'Üst Yazı',
            'scope' => 'institution_cover',
            'institution' => $institution,
            'applicationId' => null,
            'editorType' => $src['editor'],
            'editorGridType' => false,
            'initialContent' => $src['content'],
            'docCss' => $src['css'],
            'saveUrl' => route('admin.document-templates.update-institution-cover', $institution),
            'resetUrl' => route('admin.document-templates.destroy-institution-cover', $institution),
            'importWordUrl' => route('admin.document-templates.import-word-institution-cover', $institution),
            'draftsUrl' => route('admin.document-templates.drafts-institution-cover.index', $institution),
            'backUrl' => route('admin.document-templates.index'),
            'title' => $institution->name . ' — Üst Yazı Şablonu',
        ]);
    }

    /** Kurum Üst Yazı şablonunu kaydet. */
    public function updateInstitutionCover(Request $request, \App\Models\Institution $institution): JsonResponse
    {
        $this->guardAccess();
        abort_unless(! (bool) $institution->is_municipality, 403, 'Yalnızca alt kurum üst yazı şablonları düzenlenebilir.');

        $content = (string) $request->input('content_data');
        DocumentTemplateService::saveInstitution((int) $institution->id, 'cover_letter', $content);

        return response()->json(['ok' => true]);
    }

    /** Kurum Üst Yazı şablonunu sil → master/global varsayılan akışa dön. */
    public function destroyInstitutionCover(\App\Models\Institution $institution)
    {
        $this->guardAccess();
        abort_unless(! (bool) $institution->is_municipality, 403, 'Yalnızca alt kurum üst yazı şablonları düzenlenebilir.');

        DocumentTemplateService::deleteInstitution((int) $institution->id, 'cover_letter');

        return back()->with('success', $institution->name . ' üst yazı şablonu kaldırıldı. Varsayılan şablona dönüldü.');
    }

    /** Başvuruya özel taslak düzenleme: belediye personeli VEYA başvurunun kendi kurumunun personeli. */
    protected function guardApplicationScope(Application $application): void
    {
        $user = auth()->user();

        // Belediye personeli her başvuruyu düzenleyebilir.
        if ($user->isMunicipalityPersonel()) {
            return;
        }

        // Alt kurum personeli yalnızca KENDİ kurumunun başvurusunu düzenleyebilir.
        // (CELL-BASED AUTH: hücre kilitleri blade'de zaten belediye makam bölgelerini korur.)
        abort_unless(
            $user->institution_id && (int) $user->institution_id === (int) $application->institution_id,
            403,
            'Yalnızca kendi kurumunuzun başvuru belgelerini düzenleyebilirsiniz.'
        );
    }

    /** Başvuru bazlı override taslağı düzenle. */
    public function editApplication(Application $application, string $documentType): View
    {
        $this->guardApplicationScope($application);
        $this->authorize('view', $application);
        $t = DocumentTemplateService::type($documentType) ?: abort(404, 'Bilinmeyen belge tipi.');

        // GÖREV 3 (sunucu sert kilidi — URL ile dahi aşılamaz): Alt kurum personeli belgeyi
        // yalnızca DRAFT iken düzenleyebilir. Belediyeye submit ettikten sonra (status != draft)
        // editör salt-okunur görünüme değil DOĞRUDAN 403'e döner; belge tamamen kilitlenir.
        abort_unless(
            auth()->user()->isMunicipalityPersonel() || $this->applicationStatusRaw($application) === 'draft',
            403,
            'Başvuru artık taslak statüsünde değildir. Alt Kurum yalnızca görüntüleyebilir.'
        );

        $src = DocumentTemplateService::editorSource($documentType, $application);

        $bodyReadOnly = ! auth()->user()->isMunicipalityPersonel()
            && $this->applicationStatusRaw($application) !== 'draft';

        // FRONTEND → DB KÖPRÜSÜ (senkron kancası): Editördeki zemin satırlarına satır
        // kimliğini sunucudan enjekte ediyoruz. Eski (önceden kaydedilmiş) override'larda
        // data-line-id HTML'inde bulunmayabilir; bu harita sayesinde JS, satırı zemin
        // adıyla eşleyip data-line-id'yi YÜKLENİRKEN takar. Böylece metrajda M² değiştirilip
        // kaydedildiğinde sync_zemin_lines her zaman dolu gelir ve DB güncellenir.
        $application->loadMissing(['surfaceLines.surfaceType']);
        $surfaceLineIds = [];
        foreach ($application->surfaceLines ?? [] as $sl) {
            if (! $sl->surfaceType) {
                continue;
            }
            $surfaceLineIds[mb_strtolower(trim((string) $sl->surfaceType->name), 'UTF-8')] = (int) $sl->id;
        }

        return $this->editorView([
            'docType' => $documentType,
            'docLabel' => $t['label'],
            'scope' => 'application',
            'applicationId' => $application->id,
            'editorType' => $src['editor'],
            'editorGridType' => self::gridType($documentType),
            'initialContent' => $src['content'],
            'docCss' => $src['css'],
            'saveUrl' => route('admin.applications.edit-document.save', [$application, $documentType]),
            'resetUrl' => $userCanReset = auth()->user()->isMunicipalityPersonel() ? route('admin.applications.edit-document.destroy', [$application, $documentType]) : null,
            'importWordUrl' => route('admin.applications.edit-document.import-word', [$application, $documentType]),
            'draftsUrl' => route('admin.applications.edit-document.drafts.index', [$application, $documentType]),
            'backUrl' => route('admin.applications.show', $application),
            'title' => $t['label'] . ' — ' . $application->application_no,
            'readOnly' => $bodyReadOnly,
            // CANLI DOM MATEMATİĞİ (GÖREV 2): Kırmızı Çizgi kuralları + birim fiyat
            // haritası JS ReactiveMathEngine'e enjekte edilir (AykomeMath aynası).
            'math' => [
                'isDicle' => $application->isDicle(),
                'isInstitutionApp' => $application->isInstitutionApplication(),
                'isAdditionalPermit' => (bool) ($application->is_additional_permit ?? false),
                'surfacePrices' => \App\Models\SurfaceType::query()
                    ->where('active', true)
                    ->pluck('price_per_m2', 'name')
                    ->all(),
                'surfaceLineIds' => $surfaceLineIds,
            ],
        ]);
    }

    /** Başvuru statüsünü ham (string) değerine çevirir. */
    protected function applicationStatusRaw(Application $application): string
    {
        $status = $application->status;
        return $status instanceof ApplicationStatus ? $status->value : (string) $status;
    }

    public function saveApplication(Request $request, Application $application, string $documentType, \App\Services\DocumentSyncService $syncService): JsonResponse
    {
        $this->guardApplicationScope($application);
        $this->authorize('view', $application);
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        $isMuni = auth()->user()->isMunicipalityPersonel();
        $status = $this->applicationStatusRaw($application);

// GÖREV 4 (İMZA İSTİSNALARI): Alt kurum, yalnızca metrajın "KURUM/KURULUŞ" imza
        // kutusunu (metrage_sent), taahhütnamenin "RUHSATI TESLİM ALAN" hücresini
        // (taahhutname_sent) ve ruhsatın "YAPILACAK İŞİN FENNİ MESULÜ" (Firma/Sorumlu/Telefon/İmza)
        // kutusunu (ruhsat_sent) kaydedebilir. Diğer tüm belgelerde/aşamalarda 403 aynen korunur.
        $signableDoc = ($documentType === 'metraj' && $status === 'metrage_sent')
            || ($documentType === 'taahhutname' && $status === 'taahhutname_sent')
            || ($documentType === 'ruhsat' && $status === 'ruhsat_sent');
        $signOnly = ! $isMuni && $signableDoc;

        // GÖREV 2 (sunucu sert kilidi — JS bypass edilemez): Alt kurum, submit sonrası
        // (status != draft) belgeyi düzenleyip kaydedemez; yalnızca salt-okunur görür.
        abort_unless(
            $isMuni || $status === 'draft' || $signOnly,
            403,
            'Başvuru artık taslak statüsünde değildir. Alt Kurum yalnızca görüntüleyebilir.'
        );

        $content = (string) $request->input('content_data');

        // MODÜLLER ARASI SENKRON (GÖREV 3): Belediye memuru bir Excel belgesinin
        // (ruhsat/tahakkuk/metraj) sayısal hücresini düzenleyip kaydettiğinde belgedeki
        // yüzey miktarları DB'ye geri beslenir (live_sync_lines), App toplamları BAŞTAN
        // hesaplanır ve diğer evrakların eski override'ları silinir (DB'den fresh render).
        $isExcelDoc = in_array($documentType, ['ruhsat', 'tahakkuk', 'metraj'], true);

        if ($isMuni && $isExcelDoc && $request->filled('live_sync_lines')) {
            // GÖREV 3 — FRONTEND→DB ZİNCİRİ: Editörün DOM'undan gelen [{id, val}] listesi.
            $updates = json_decode((string) $request->input('live_sync_lines'), true);
            // GÖREV 4 (debug): Payload'ın backend'e ulaştığını laravel.log'da doğrula.
            \Illuminate\Support\Facades\Log::info('AYKOME live_sync_lines', [
                'application' => $application->id,
                'document_type' => $documentType,
                'count' => is_array($updates) ? count($updates) : 0,
                'payload' => is_array($updates) ? $updates : $request->input('live_sync_lines'),
            ]);
            if (is_array($updates) && count($updates) > 0) {
                // 1) Zemin satırlarını (miktar) DB'de güncelle + 2) Parayı BAŞTAN hesapla.
                $this->applyLiveSyncLines($application, $updates);
                // 3) DÜNYANIN EN KRİTİK HAMLESİ: DİĞER evrakların (Tahakkuk/Ruhsat/Metraj)
                //    eski kayıtlı HTML override'larını SİL → modül DB'deki yeni miktarı alıp
                //    fatura+KDV keserek SIFIRDAN TERTEMİZ FRESH RENDER üretsin.
                foreach (['ruhsat', 'tahakkuk', 'metraj'] as $exDoc) {
                    if ($exDoc !== $documentType) {
                        DocumentTemplateService::deleteOverride($application, $exDoc);
                    }
                }
            } elseif ($isMuni && $isExcelDoc) {
                // live_sync_lines boş döndü (ör. veri kancası yok) → isim tabanlı yedek.
                $syncService->syncFromDocument($application, $documentType, $content);
            }
        } elseif ($isMuni && $isExcelDoc) {
            // Yedek (eski veri): isim tabanlı ayrıştırma (data-aykome-surface).
            $syncService->syncFromDocument($application, $documentType, $content);
        }

        // GÖREV 4 (SUNUCU GÜVENLİĞİ): Alt kurum imzasını kaydederken yalnızca
        // data-sign-editable imza hücresi korunur; geri kalan her şey yeniden üretilir.
        // Metrajda bu hücre tablo sarmalayıcıyla taşınır; taahhütname/ruhsatta doğrudan
        // eleman düzeyinde birleştirilir.
        if ($signOnly) {
            $base = DocumentTemplateService::signatureSaveBase($documentType, $application);
            $content = DocumentTemplateService::mergeSignatureOnly($base, $content, $documentType === 'metraj');
        }

        DocumentTemplateService::saveOverride($application, $documentType, $content);

        return response()->json(['ok' => true]);
    }

    /**
     * GÖREV 3 — FRONTEND→DB ZİNCİRİ: Editörden gelen [{id, val}] listesindeki zemin
     * satırlarının miktarını DB'de günceller ve Eyyübiye matematiğiyle (AykomeMath)
     * App toplamlarını (amount, KDV, harç, keşif, teminat, genel) BAŞTAN kurar.
     * Güvenlik: yalnızca BU başvurunun satırları değiştirilebilir (application_id scope).
     */
    protected function applyLiveSyncLines(Application $application, array $updates): void
    {
        $ids = collect($updates)->pluck('id')->filter()->map(fn ($v) => (int) $v)->all();
        $lines = ApplicationSurfaceArea::where('application_id', $application->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($updates as $u) {
            $line = $lines->get((int) ($u['id'] ?? 0));
            if (! $line) {
                continue;
            }
            $line->update(['quantity' => max((float) ($u['val'] ?? 0), 0)]);
        }

        app(PricingService::class)->recalculateTotals($application);
    }

    /** Başvuruya özel taslağı sil → global/varsayılan akışa dön. (Yalnızca belediye.) */
    public function destroyApplication(Application $application, string $documentType)
    {
        abort_unless(auth()->user()->isMunicipalityPersonel(), 403, 'Şablon yönetimi için yetkiniz yok.');
        $this->authorize('view', $application);
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        DocumentTemplateService::deleteOverride($application, $documentType);

        return back()->with('success', 'Başvuruya özel taslak kaldırıldı. Varsayılan şablona dönüldü.');
    }

    /* ─── TAM_WORLD_YAPISI.md Aşama 1 — Word (.docx) içe aktarma ────────────────────── */

    /**
     * Yüklenen .docx dosyasını HTML'e çevirir. Çağıran metodlar kendi yetki
     * kontrolünü yaptıktan SONRA bunu çağrır — bu metod yetki kontrolü YAPMAZ.
     */
    private function handleWordImport(Request $request, string $documentType = 'cover_letter'): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:docx|max:10240',
        ], [
            'file.mimes' => 'Yalnızca .docx (Word 2007+) dosyaları yüklenebilir.',
            'file.max' => 'Dosya en fazla 10MB olabilir.',
        ]);

        try {
            $html = DocumentTemplateService::importWordToHtml($request->file('file')->getRealPath(), $documentType);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Word içe aktarma başarısız', ['error' => $e->getMessage()]);

            return response()->json([
                'ok' => false,
                'message' => 'Word dosyası okunamadı. Dosyanın bozuk olmadığından ve .docx (eski .doc DEĞiİL) formatında olduğundan emin olun.',
            ], 422);
        }

        return response()->json(['ok' => true, 'html' => $html]);
    }

    /** Global (master) şablona Word içe aktar. */
    public function importWordGlobal(Request $request, string $documentType): JsonResponse
    {
        $this->guardAccess();
        abort_unless(DocumentTemplateService::isValid($documentType), 404);
        if ($this->isInstitutionScope() && $documentType === 'on_kazi') {
            abort(403, 'Ön Kazı şablonu belediye yetkisindedir.');
        }

        return $this->handleWordImport($request, $documentType);
    }

    /** Kurum Üst Yazı şablonuna Word içe aktar. */
    public function importWordInstitutionCover(Request $request, \App\Models\Institution $institution): JsonResponse
    {
        $this->guardAccess();
        abort_unless(! (bool) $institution->is_municipality, 403, 'Yalnızca alt kurum üst yazı şablonları düzenlenebilir.');

        return $this->handleWordImport($request, 'cover_letter');
    }

    /** Başvuruya özel taslağa Word içe aktar. */
    public function importWordApplication(Request $request, Application $application, string $documentType): JsonResponse
    {
        $this->guardApplicationScope($application);
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        return $this->handleWordImport($request, $documentType);
    }

    /* ─── 16.08 5. tur — Taslak Kütüphanesi (Word/manuel çoklu sürüm kasası) ──────── */

    private function draftsList(string $scope, ?int $scopeId, string $documentType): JsonResponse
    {
        $drafts = \App\Models\DocumentTemplateDraft::query()
            ->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->where('document_type', $documentType)
            ->orderByDesc('id')
            ->get(['id', 'name', 'source', 'created_at'])
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'source' => $d->source,
                'created_at' => optional($d->created_at)->format('d.m.Y H:i'),
            ]);

        return response()->json(['ok' => true, 'drafts' => $drafts]);
    }

    private function draftsStore(Request $request, string $scope, ?int $scopeId, string $documentType): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'content_data' => 'required|string',
            'source' => 'nullable|string|in:manual,word_import',
        ]);

        $draft = \App\Models\DocumentTemplateDraft::create([
            'scope' => $scope,
            'scope_id' => $scopeId,
            'document_type' => $documentType,
            'name' => $data['name'],
            'content_data' => $data['content_data'],
            'source' => $data['source'] ?? 'manual',
            'created_by' => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'draft' => ['id' => $draft->id, 'name' => $draft->name]]);
    }

    private function draftsShow(string $scope, ?int $scopeId, string $documentType, int $draftId): JsonResponse
    {
        $draft = \App\Models\DocumentTemplateDraft::query()
            ->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->where('document_type', $documentType)
            ->findOrFail($draftId);

        return response()->json(['ok' => true, 'html' => $draft->content_data, 'name' => $draft->name]);
    }

    private function draftsDestroy(string $scope, ?int $scopeId, string $documentType, int $draftId): JsonResponse
    {
        \App\Models\DocumentTemplateDraft::query()
            ->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->where('document_type', $documentType)
            ->where('id', $draftId)
            ->delete();

        return response()->json(['ok' => true]);
    }

    // Global taslaklar
    public function draftsIndexGlobal(string $documentType): JsonResponse
    {
        $this->guardAccess();
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        return $this->draftsList('global', null, $documentType);
    }

    public function draftsStoreGlobal(Request $request, string $documentType): JsonResponse
    {
        $this->guardAccess();
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        return $this->draftsStore($request, 'global', null, $documentType);
    }

    public function draftsShowGlobal(string $documentType, int $draft): JsonResponse
    {
        $this->guardAccess();

        return $this->draftsShow('global', null, $documentType, $draft);
    }

    public function draftsDestroyGlobal(string $documentType, int $draft): JsonResponse
    {
        $this->guardAccess();

        return $this->draftsDestroy('global', null, $documentType, $draft);
    }

    // Kurum Üst Yazı taslakları
    public function draftsIndexInstitutionCover(\App\Models\Institution $institution): JsonResponse
    {
        $this->guardAccess();

        return $this->draftsList('institution', $institution->id, 'cover_letter');
    }

    public function draftsStoreInstitutionCover(Request $request, \App\Models\Institution $institution): JsonResponse
    {
        $this->guardAccess();

        return $this->draftsStore($request, 'institution', $institution->id, 'cover_letter');
    }

    public function draftsShowInstitutionCover(\App\Models\Institution $institution, int $draft): JsonResponse
    {
        $this->guardAccess();

        return $this->draftsShow('institution', $institution->id, 'cover_letter', $draft);
    }

    public function draftsDestroyInstitutionCover(\App\Models\Institution $institution, int $draft): JsonResponse
    {
        $this->guardAccess();

        return $this->draftsDestroy('institution', $institution->id, 'cover_letter', $draft);
    }

    // Başvuruya özel taslaklar
    public function draftsIndexApplication(Application $application, string $documentType): JsonResponse
    {
        $this->guardApplicationScope($application);
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        return $this->draftsList('application', $application->id, $documentType);
    }

    public function draftsStoreApplication(Request $request, Application $application, string $documentType): JsonResponse
    {
        $this->guardApplicationScope($application);
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        return $this->draftsStore($request, 'application', $application->id, $documentType);
    }

    public function draftsShowApplication(Application $application, string $documentType, int $draft): JsonResponse
    {
        $this->guardApplicationScope($application);

        return $this->draftsShow('application', $application->id, $documentType, $draft);
    }

    public function draftsDestroyApplication(Application $application, string $documentType, int $draft): JsonResponse
    {
        $this->guardApplicationScope($application);

        return $this->draftsDestroy('application', $application->id, $documentType, $draft);
    }

    protected function editorView(array $data): View
    {
        // GÖREV 2 (CELL-BASED AUTH): Editöre oturum rolünü ilet — alt kurum oturumunda
        // belediye makam hücreleri JS tarafında kilitli kalır (contenteditable="false").
        $data['isMuni'] = auth()->user()->isMunicipalityPersonel();

        // GÖREV (KAZIT): Editör düzenlenebilir durumdaysa (readOnly=false) içeriğin
        // contenteditable yaşam döngüsünü garanti et. Kaydedilen şablonlarda zamanla
        // contenteditable özniteliği düşmüşse bile "Düzenle (Kaydet)" editörü yeniden
        // düzenlenebilir görünür; salt-okunur durumdaysa (readOnly=true) kilitli kalır.
        $data['initialContent'] = DocumentTemplateService::ensureContentEditable(
            $data['initialContent'] ?? '',
            ! ($data['readOnly'] ?? false)
        );

        // BİLGİ KATMANI: editör sağ panelindeki alan kataloğu (tüm belge tipleri).
        $data['fieldCatalog'] = DocumentTemplateService::fieldCatalog();

        // 16.08 6. tur FIX: A4 genişlik sabitlemesi belge tipine göre değişmeli —
        // Kazı Metraj (landscape=true) yatay A4'tur, portrait (210mm) ile SABiTlenirse
        // tablo sıkışır/bozulur. docType'a bakıp doğru yönelimi ilet.
        $data['isLandscape'] = ! empty(DocumentTemplateService::TYPES[$data['docType'] ?? '']['landscape']);

        // 16.08 14. tur FIX: #doc-editor'ün padding'i artık PDF/e-imza çıktısıyla
        // AYNI TEK kaynaktan (bkz. DocumentTemplateService::A4_CONTAINER_PADDING) —
        // kullanıcının taslakta serbest konumlandırdığı bloklar artık başvuru
        // modülünde AYNI mesafede çıkar (önceden editör bağımsız, farklı bir
        // padding değeri kullanıyordu).
        $data['containerPadding'] = DocumentTemplateService::A4_CONTAINER_PADDING;

        return view('admin.document-templates.editor', $data);
    }

    /** Excel/Grid tabanlı şablon tipi mi? (satır ekle/sil butonları için) */
    protected static function gridType(string $documentType): bool
    {
        return DocumentTemplateService::editor($documentType) === 'excel';
    }
}
