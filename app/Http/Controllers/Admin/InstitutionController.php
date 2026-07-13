<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    private const TYPES = [
        'TEDAŞ'             => 'TEDAŞ',
        'Türk Telekom'      => 'Türk Telekom',
        'ŞUSKİ'             => 'ŞUSKİ',
        'İlçe Belediyesi'   => 'İlçe Belediyesi',
        'DSİ'               => 'DSİ',
        'Karayolları'       => 'Karayolları',
        'Doğalgaz'          => 'Doğalgaz',
        'Özel Firma'        => 'Özel Firma',
        'Diğer'             => 'Diğer',
    ];

    public function index(Request $request): View
    {
        $query = Institution::query()->withCount('applications')->orderBy('name');

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('type', 'like', "%{$q}%")
                   ->orWhere('authorized_person', 'like', "%{$q}%");
            });
        }

        return view('admin.institutions.index', [
            'institutions' => $query->paginate(15)->withQueryString(),
            'types'        => self::TYPES,
            'q'            => $q ?? '',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Institution::query()->withCount('applications');

        // Total BEFORE search filter (for DataTables recordsTotal)
        $recordsTotal = Institution::count();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('authorized_person', 'like', "%{$search}%")
                  ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $orderCol = match((int) $request->input('order.0.column', 0)) {
            1 => 'name',
            2 => 'type',
            3 => 'authorized_person',
            4 => 'tax_number',
            default => 'id',
        };
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $rows = $query
            ->orderBy($orderCol, $orderDir)
            ->offset((int) $request->input('start', 0))
            ->limit((int) $request->input('length', 15))
            ->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $total,
            'data'            => $rows->map(fn ($i) => [
                $i->id,
                e($i->name),
                e($i->type ?? '—'),
                e($i->authorized_person ?? '—'),
                e($i->tax_number ?? '—'),
                e($i->phone ?? '—'),
                (int) $i->applications_count,
                $i->id,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'type'              => ['nullable', 'string', 'max:100'],
            'authorized_person' => ['nullable', 'string', 'max:255'],
            'tax_number'        => ['nullable', 'string', 'max:20'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'email'             => ['nullable', 'email', 'max:255'],
            'address'           => ['nullable', 'string', 'max:1000'],
            'color_code'        => ['nullable', 'string', 'max:7'],
            'is_municipality'   => ['boolean'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_municipality'] = $request->boolean('is_municipality');

        Institution::create($data);

        return back()->with('success', 'Kurum başarıyla eklendi.');
    }

    public function editJson(Institution $institution): JsonResponse
    {
        return response()->json($institution->only([
            'id', 'name', 'type', 'authorized_person', 'tax_number',
            'phone', 'email', 'address', 'color_code', 'is_municipality',
        ]));
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'type'              => ['nullable', 'string', 'max:100'],
            'authorized_person' => ['nullable', 'string', 'max:255'],
            'tax_number'        => ['nullable', 'string', 'max:20'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'email'             => ['nullable', 'email', 'max:255'],
            'address'           => ['nullable', 'string', 'max:1000'],
            'color_code'        => ['nullable', 'string', 'max:7'],
            'is_municipality'   => ['boolean'],
        ]);

        $data['is_municipality'] = $request->boolean('is_municipality');

        $institution->update($data);

        return back()->with('success', 'Kurum güncellendi.');
    }

    public function destroy(Institution $institution): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // Prevent deleting if applications exist
        if ($institution->applications()->exists()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Bu kuruma ait başvurular bulunuyor, silinemez.'], 422);
            }
            return back()->with('error', 'Bu kuruma ait başvurular bulunuyor, silinemez.');
        }

        $institution->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Kurum silindi.']);
        }

        return back()->with('success', 'Kurum silindi.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (Institution::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
