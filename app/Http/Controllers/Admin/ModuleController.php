<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationModule;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = ApplicationModule::withCount(['fields', 'templates'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.modules.index', compact('modules'));
    }

    public function create()
    {
        return view('admin.modules.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:application_modules,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
        ]);

        $maxOrder = ApplicationModule::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $module = ApplicationModule::create($validated);

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->with('success', 'Modül başarıyla oluşturuldu.');
    }

    public function show(ApplicationModule $module)
    {
        $module->load(['fields', 'templates', 'sequences']);

        return view('admin.modules.show', compact('module'));
    }

    public function edit(ApplicationModule $module)
    {
        $module->load(['fields', 'templates', 'sequences']);

        return view('admin.modules.edit', compact('module'));
    }

    public function update(Request $request, ApplicationModule $module)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:application_modules,slug,' . $module->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;

        // Merge config with existing config instead of replacing
        $newConfig = $request->input('config', []);
        $module->config = array_merge($module->config ?? [], $newConfig);
        $validated['config'] = $module->config;

        $module->update($validated);

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->with('success', 'Modül başarıyla güncellendi.');
    }

    public function destroy(ApplicationModule $module)
    {
        $module->delete();

        return redirect()
            ->route('admin.modules.index')
            ->with('success', 'Modül başarıyla silindi.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($validated['order'] as $sortOrder => $moduleId) {
            ApplicationModule::where('id', $moduleId)->update(['sort_order' => $sortOrder]);
        }

        return response()->json(['success' => true]);
    }
}
