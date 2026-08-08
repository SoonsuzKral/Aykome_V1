<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationModule;
use App\Models\ApplicationModuleField;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModuleFieldController extends Controller
{
    public function store(Request $request, ApplicationModule $module)
    {
        $validated = $request->validate([
            'field_name' => 'required|string|max:100',
            'field_type' => 'required|string|in:' . implode(',', ApplicationModuleField::FIELD_TYPES),
            'label' => 'required|string|max:255',
            'placeholder' => 'nullable|string|max:255',
            'default_value' => 'nullable|string',
            'help_text' => 'nullable|string',
            'field_options_text' => 'nullable|string',
            'width' => 'nullable|string|in:' . implode(',', ApplicationModuleField::WIDTHS),
            'is_active' => 'boolean',
        ]);

        // Convert newline-separated options text to array
        if (!empty($validated['field_options_text'])) {
            $options = array_filter(array_map('trim', explode("\n", $validated['field_options_text'])));
            $validated['field_options'] = $options;
        }
        unset($validated['field_options_text']);

        // Handle is_required -> validation_rules
        $validationRules = [];
        if ($request->boolean('is_required')) {
            $validationRules[] = 'required';
        }
        $validated['validation_rules'] = $validationRules;

        $validated['application_module_id'] = $module->id;
        $validated['sort_order'] = $module->fields()->max('sort_order') + 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        ApplicationModuleField::create($validated);

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->withFragment('fields')
            ->with('success', 'Alan başarıyla eklendi.');
    }

    public function update(Request $request, ApplicationModule $module, ApplicationModuleField $field)
    {
        abort_unless($field->application_module_id === $module->id, 404);

        $validated = $request->validate([
            'field_name' => 'required|string|max:100',
            'field_type' => 'required|string|in:' . implode(',', ApplicationModuleField::FIELD_TYPES),
            'label' => 'required|string|max:255',
            'placeholder' => 'nullable|string|max:255',
            'default_value' => 'nullable|string',
            'help_text' => 'nullable|string',
            'field_options_text' => 'nullable|string',
            'width' => 'nullable|string|in:' . implode(',', ApplicationModuleField::WIDTHS),
            'is_active' => 'boolean',
        ]);

        // Convert newline-separated options text to array
        if (isset($validated['field_options_text'])) {
            if (!empty($validated['field_options_text'])) {
                $options = array_filter(array_map('trim', explode("\n", $validated['field_options_text'])));
                $validated['field_options'] = $options;
            } else {
                $validated['field_options'] = [];
            }
            unset($validated['field_options_text']);
        }

        // Handle is_required -> validation_rules
        $validationRules = [];
        if ($request->boolean('is_required')) {
            $validationRules[] = 'required';
        }
        $validated['validation_rules'] = $validationRules;

        $validated['is_active'] = $validated['is_active'] ?? false;
        $field->update($validated);

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->withFragment('fields')
            ->with('success', 'Alan başarıyla güncellendi.');
    }

    public function destroy(ApplicationModule $module, ApplicationModuleField $field)
    {
        abort_unless($field->application_module_id === $module->id, 404);
        $field->delete();

        return redirect()
            ->route('admin.modules.edit', $module->id)
            ->withFragment('fields')
            ->with('success', 'Alan başarıyla silindi.');
    }

    public function reorder(Request $request, ApplicationModule $module): JsonResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($validated['order'] as $sortOrder => $fieldId) {
            ApplicationModuleField::where('id', $fieldId)
                ->where('application_module_id', $module->id)
                ->update(['sort_order' => $sortOrder]);
        }

        return response()->json(['success' => true]);
    }
}
