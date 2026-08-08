<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationModule;
use App\Models\ApplicationModuleTemplate;
use Illuminate\Http\Request;

class ModuleTemplateController extends Controller
{
    public function store(Request $request, ApplicationModule $module)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:100',
            'template_name' => 'required|string|max:255',
            'content_data' => 'nullable|string',
            'editor_type' => 'required|string|in:' . implode(',', ApplicationModuleTemplate::EDITOR_TYPES),
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $validated['application_module_id'] = $module->id;
        $validated['sort_order'] = $validated['sort_order'] ?? ($module->templates()->max('sort_order') + 1);
        $validated['is_active'] = $validated['is_active'] ?? true;

        ApplicationModuleTemplate::create($validated);

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->withFragment('templates')
            ->with('success', 'Şablon başarıyla eklendi.');
    }

    public function update(Request $request, ApplicationModule $module, ApplicationModuleTemplate $template)
    {
        abort_unless($template->application_module_id === $module->id, 404);

        $validated = $request->validate([
            'document_type' => 'required|string|max:100',
            'template_name' => 'required|string|max:255',
            'content_data' => 'nullable|string',
            'editor_type' => 'required|string|in:' . implode(',', ApplicationModuleTemplate::EDITOR_TYPES),
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;
        $template->update($validated);

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->withFragment('templates')
            ->with('success', 'Şablon başarıyla güncellendi.');
    }

    public function destroy(ApplicationModule $module, ApplicationModuleTemplate $template)
    {
        abort_unless($template->application_module_id === $module->id, 404);
        $template->delete();

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->withFragment('templates')
            ->with('success', 'Şablon başarıyla silindi.');
    }
}
