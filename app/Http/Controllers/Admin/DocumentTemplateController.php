<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\DocumentTemplateService;
use App\Enums\ApplicationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    protected function guardAccess(): void
    {
        $user = auth()->user();

        // Alt kurum personeli (institution-manager/staff) yalnızca KENDİ kurumunun
        // Üst Yazı şablonunu düzenleyebilir. Diğer tüm erişim belediye yönetimi içindir.
        if (! $user->isMunicipalityPersonel()) {
            abort_unless(
                $user->institution_id && $user->hasAnyRole(['institution-manager', 'institution-staff', 'institution-admin']),
                403,
                'Şablon yönetimi için yetkiniz yok.'
            );
        }
    }

    /** Alt kurum kullanıcısı mı? (yalnızca Üst Yazı ve kendi kurumu) */
    protected function isInstitutionScope(): bool
    {
        $user = auth()->user();

        return ! $user->isMunicipalityPersonel() && $user->institution_id;
    }

    /** 📝 Taslak / Şablon Yönetimi — 4 kutu. */
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

        return view('admin.document-templates.index', [
            'types' => $types,
            'institutionScope' => $institutionScope,
            'institution' => $institutionScope ? $user->institution : null,
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
        // yüzey miktarları DB'ye geri beslenir (delta), App toplamları BAŞTAN hesaplanır
        // ve orantılı dağıtım payı surface_sync_log'a yazılır. DB, taslakla senkron kalır.
        if ($isMuni && in_array($documentType, ['ruhsat', 'tahakkuk', 'metraj'], true)) {
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

    /** Başvuruya özel taslağı sil → global/varsayılan akışa dön. (Yalnızca belediye.) */
    public function destroyApplication(Application $application, string $documentType)
    {
        abort_unless(auth()->user()->isMunicipalityPersonel(), 403, 'Şablon yönetimi için yetkiniz yok.');
        $this->authorize('view', $application);
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        DocumentTemplateService::deleteOverride($application, $documentType);

        return back()->with('success', 'Başvuruya özel taslak kaldırıldı. Varsayılan şablona dönüldü.');
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

        return view('admin.document-templates.editor', $data);
    }

    /** Excel/Grid tabanlı şablon tipi mi? (satır ekle/sil butonları için) */
    protected static function gridType(string $documentType): bool
    {
        return DocumentTemplateService::editor($documentType) === 'excel';
    }
}
