<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurfaceType;
use Illuminate\Http\Request;

class SurfaceTypeController extends Controller
{
    public function index()
    {
        $surfaceTypes = SurfaceType::orderBy('name')->get();
        return view('admin.surface_types.index', compact('surfaceTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:surface_types,name'],
            'price_per_m2'=> ['required', 'numeric', 'min:0', 'max:999999.99'],
            'color_code'  => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'active'      => ['boolean'],
        ]);

        $data['active'] = $request->boolean('active', true);

        SurfaceType::create($data);

        return back()->with('success', 'Zemin tipi eklendi.');
    }

    public function update(Request $request, SurfaceType $surfaceType)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:surface_types,name,' . $surfaceType->id],
            'price_per_m2'=> ['required', 'numeric', 'min:0', 'max:999999.99'],
            'color_code'  => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'active'      => ['boolean'],
        ]);

        $data['active'] = $request->boolean('active', true);

        $surfaceType->update($data);

        return back()->with('success', 'Zemin tipi güncellendi.');
    }

    public function destroy(SurfaceType $surfaceType)
    {
        if ($surfaceType->applicationSurfaceAreas()->exists()) {
            return back()->with('error', 'Bu zemin tipine bağlı başvuru kayıtları mevcut. Silmek yerine pasife alın.');
        }

        $surfaceType->delete();

        return back()->with('success', 'Zemin tipi silindi.');
    }
}
