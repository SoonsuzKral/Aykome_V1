<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\DocumentTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    protected function guardAccess(): void
    {
        abort_unless(
            auth()->user()->hasAnyRole(['super-admin', 'municipality-admin']),
            403,
            'Şablon yönetimi için yetkiniz yok.'
        );
    }

    /** 📝 Taslak / Şablon Yönetimi — 4 kutu. */
    public function index(): View
    {
        $this->guardAccess();

        $types = [];
        foreach (DocumentTemplateService::TYPES as $key => $t) {
            $types[] = [
                'key' => $key,
                'label' => $t['full'],
                'desc' => $t['desc'],
                'editor' => $t['editor'],
                'icon' => $t['icon'],
                'hasTemplate' => DocumentTemplateService::globalContent($key) !== null,
                'editUrl' => route('admin.document-templates.edit', $key),
            ];
        }

        return view('admin.document-templates.index', ['types' => $types]);
    }

    /** Global (master) şablonu düzenle — tam ekran Word/Excel editörü. */
    public function editGlobal(string $documentType): View
    {
        $this->guardAccess();
        $t = DocumentTemplateService::type($documentType) ?: abort(404, 'Bilinmeyen belge tipi.');

        $src = DocumentTemplateService::editorSource($documentType, null);

        return $this->editorView([
            'docType' => $documentType,
            'docLabel' => $t['full'],
            'scope' => 'global',
            'applicationId' => null,
            'editorType' => $src['editor'],
            'initialContent' => $src['content'],
            'docCss' => $src['css'],
            'saveUrl' => route('admin.document-templates.update', $documentType),
            'resetUrl' => null,
            'backUrl' => route('admin.document-templates.index'),
            'title' => $t['full'],
        ]);
    }

    public function updateGlobal(Request $request, string $documentType): JsonResponse
    {
        $this->guardAccess();
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        DocumentTemplateService::saveGlobal($documentType, (string) $request->input('content_data'));

        return response()->json(['ok' => true]);
    }

    /** Başvuru bazlı override taslağı düzenle. */
    public function editApplication(Application $application, string $documentType): View
    {
        $this->guardAccess();
        $this->authorize('view', $application);
        $t = DocumentTemplateService::type($documentType) ?: abort(404, 'Bilinmeyen belge tipi.');

        $src = DocumentTemplateService::editorSource($documentType, $application);

        return $this->editorView([
            'docType' => $documentType,
            'docLabel' => $t['label'],
            'scope' => 'application',
            'applicationId' => $application->id,
            'editorType' => $src['editor'],
            'initialContent' => $src['content'],
            'docCss' => $src['css'],
            'saveUrl' => route('admin.applications.edit-document.save', [$application, $documentType]),
            'resetUrl' => route('admin.applications.edit-document.destroy', [$application, $documentType]),
            'backUrl' => route('admin.applications.show', $application),
            'title' => $t['label'] . ' — ' . $application->application_no,
        ]);
    }

    public function saveApplication(Request $request, Application $application, string $documentType): JsonResponse
    {
        $this->guardAccess();
        $this->authorize('view', $application);
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        DocumentTemplateService::saveOverride($application, $documentType, (string) $request->input('content_data'));

        return response()->json(['ok' => true]);
    }

    /** Başvuruya özel taslağı sil → global/varsayılan akışa dön. */
    public function destroyApplication(Application $application, string $documentType)
    {
        $this->guardAccess();
        $this->authorize('view', $application);
        abort_unless(DocumentTemplateService::isValid($documentType), 404);

        DocumentTemplateService::deleteOverride($application, $documentType);

        return back()->with('success', 'Başvuruya özel taslak kaldırıldı. Varsayılan şablona dönüldü.');
    }

    protected function editorView(array $data): View
    {
        return view('admin.document-templates.editor', $data);
    }
}
