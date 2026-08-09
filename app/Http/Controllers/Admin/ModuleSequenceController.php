<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationModule;
use App\Models\ApplicationModuleSequence;
use Illuminate\Http\Request;

class ModuleSequenceController extends Controller
{
    public function store(Request $request, ApplicationModule $module)
    {
        $validated = $request->validate([
            'application_type' => 'required|string|max:100',
            'sort_order' => 'nullable|integer',
            'config' => 'nullable|array',
        ]);

        $validated['application_module_id'] = $module->id;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ApplicationModuleSequence::create($validated);

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->withFragment('sequence')
            ->with('success', 'Sıralama başarıyla eklendi.');
    }

    public function update(Request $request, ApplicationModule $module, ApplicationModuleSequence $sequence)
    {
        abort_unless($sequence->application_module_id === $module->id, 404);

        $validated = $request->validate([
            'application_type' => 'required|string|max:100',
            'sort_order' => 'nullable|integer',
            'config' => 'nullable|array',
        ]);

        $sequence->update($validated);

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->withFragment('sequence')
            ->with('success', 'Sıralama başarıyla güncellendi.');
    }

    public function destroy(ApplicationModule $module, ApplicationModuleSequence $sequence)
    {
        abort_unless($sequence->application_module_id === $module->id, 404);
        $sequence->delete();

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->withFragment('sequence')
            ->with('success', 'Sıralama başarıyla silindi.');
    }
}
