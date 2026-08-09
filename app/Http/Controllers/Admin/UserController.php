<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // ── Tenant-scoped base query ──────────────────────────────────────────────
    private function scopedQuery(): Builder
    {
        $me    = auth()->user();
        $query = User::query()->with(['institution', 'roles']);

        // A) Super-admin → herkesi görür
        if ($me->hasRole('super-admin')) {
            return $query;
        }

        // B) Municipality-admin: users.view_all_scoped yetkisi varsa tüm alt
        //    kurumların personellerini görür (super-adminler dahil edilmez)
        if ($me->hasRole('municipality-admin')) {
            if ($me->can('users.view_all_scoped')) {
                // Tüm non-super-admin kullanıcılar (sistemdeki tüm belediye + alt kurum)
                return $query->whereDoesntHave(
                    'roles', fn (Builder $q) => $q->where('name', 'super-admin')
                );
            }
            // Yetki yoksa sadece kendi kurumu
            return $query
                ->where('institution_id', $me->institution_id)
                ->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'super-admin'));
        }

        // C) municipality-staff: her zaman sadece kendi kurumu, super-admin hariç
        if ($me->hasRole('municipality-staff')) {
            return $query
                ->where('institution_id', $me->institution_id)
                ->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'super-admin'));
        }

        // D) institution-manager / institution-staff: yalnızca kendi kurumu, üst roller hariç
        return $query
            ->where('institution_id', $me->institution_id)
            ->whereDoesntHave('roles', fn (Builder $q) => $q->whereIn('name', [
                'super-admin', 'municipality-admin', 'municipality-staff',
            ]));
    }

    // ── Mevcut kullanıcının atayabileceği rol listesi ─────────────────────────
    private function allowedRoles(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Role::query()->orderBy('name');

        if (! auth()->user()->hasRole('super-admin')) {
            $query->where('name', '!=', 'super-admin');
        }

        return $query->get();
    }

    // ── Mevcut kullanıcının görebileceği kurum listesi ────────────────────────
    private function allowedInstitutions(): \Illuminate\Database\Eloquent\Collection
    {
        $me = auth()->user();

        if ($me->hasRole('super-admin')) {
            return Institution::query()->orderBy('name')->get(['id', 'name']);
        }

        return Institution::query()
            ->where('id', $me->institution_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ── index ─────────────────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'roles'        => $this->allowedRoles(),
            'institutions' => $this->allowedInstitutions(),
        ]);
    }

    // ── DataTables AJAX endpoint ──────────────────────────────────────────────
    public function data(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $base         = $this->scopedQuery();
        $recordsTotal = (clone $base)->count();

        if ($search = $request->input('search.value')) {
            $base->where(function ($q) use ($search): void {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role_filter')) {
            $base->role($role);
        }

        $total = $base->count();

        $colMap     = ['id', 'name', 'email', 'institution_id'];
        $orderCol   = (int) $request->input('order.0.column', 0);
        $orderDir   = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderField = $colMap[$orderCol] ?? 'name';

        $rows = $base
            ->orderBy($orderField, $orderDir)
            ->offset((int) $request->input('start', 0))
            ->limit((int) $request->input('length', 15))
            ->get();

        $roleLabels = [
            'super-admin'         => ['badge' => 'bg-violet-100 text-violet-700', 'label' => 'Super Admin'],
            'municipality-admin'  => ['badge' => 'bg-blue-100 text-blue-700',    'label' => 'Belediye Yöneticisi'],
            'municipality-staff'  => ['badge' => 'bg-sky-100 text-sky-700',      'label' => 'Belediye Personeli'],
            'institution-manager' => ['badge' => 'bg-indigo-100 text-indigo-700', 'label' => 'Kurum Yöneticisi'],
            'institution-staff'   => ['badge' => 'bg-cyan-100 text-cyan-700',    'label' => 'Kurum Personeli'],
            'field-team'          => ['badge' => 'bg-amber-100 text-amber-700',  'label' => 'Saha Personeli'],
        ];

        $data = $rows->map(function (User $user) use ($roleLabels): array {
            $rolesHtml = $user->roles->map(function ($r) use ($roleLabels): string {
                $info = $roleLabels[$r->name] ?? ['badge' => 'bg-slate-100 text-slate-600', 'label' => $r->name];
                return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold '.$info['badge'].'">'.e($info['label']).'</span>';
            })->join(' ');

            $active      = $user->is_active ?? true;
            $activeBadge = $active
                ? '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-700">Aktif</span>'
                : '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-red-100 text-red-600">Pasif</span>';

            return [
                $user->id,
                e($user->name),
                e($user->email),
                e($user->institution?->name ?? '—'),
                $rolesHtml ?: '<span class="text-gray-400 text-xs">—</span>',
                $activeBadge,
                $user->id,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    // ── create ────────────────────────────────────────────────────────────────
    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'institutions' => $this->allowedInstitutions(),
            'roles'        => $this->allowedRoles(),
        ]);
    }

    // ── store ─────────────────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $allowed = $this->allowedRoles()->pluck('name')->all();

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'       => ['required', 'confirmed', Rules\Password::defaults()],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'roles'          => ['nullable', 'array'],
            'roles.*'        => ['in:' . implode(',', $allowed)],
        ]);

        // Kurum dışı kullanıcı oluşturmayı engelle (super-admin hariç)
        $me = auth()->user();
        if (! $me->hasRole('super-admin') && isset($data['institution_id'])
            && (int) $data['institution_id'] !== (int) $me->institution_id) {
            abort(403, 'Başka bir kuruma kullanıcı ekleyemezsiniz.');
        }

        $user = User::query()->create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => Hash::make($data['password']),
            'institution_id'     => $data['institution_id'] ?? null,
            'email_verified_at'  => now(),
        ]);

        if (! empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return redirect()->route('admin.users.show', $user)->with('success', 'Kullanıcı oluşturuldu.');
    }

    // ── show ──────────────────────────────────────────────────────────────────
    public function show(User $user): View
    {
        $this->authorize('view', $user);
        $this->abortIfOutOfScope($user);

        $user->load(['institution', 'roles', 'permissions']);

        return view('admin.users.show', compact('user'));
    }

    // ── edit ──────────────────────────────────────────────────────────────────
    public function edit(User $user): View
    {
        $this->authorize('update', $user);
        $this->abortIfOutOfScope($user);

        return view('admin.users.edit', [
            'user'         => $user,
            'institutions' => $this->allowedInstitutions(),
            'roles'        => $this->allowedRoles(),
        ]);
    }

    // ── update ────────────────────────────────────────────────────────────────
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $this->abortIfOutOfScope($user);

        $allowed = $this->allowedRoles()->pluck('name')->all();

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password'       => ['nullable', 'confirmed', Rules\Password::defaults()],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'roles'          => ['nullable', 'array'],
            'roles.*'        => ['in:' . implode(',', $allowed)],
        ]);

        $user->fill([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'institution_id' => $data['institution_id'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles'] ?? []);
        }

        return redirect()->route('admin.users.show', $user)->with('success', 'Kullanıcı güncellendi.');
    }

    // ── destroy ───────────────────────────────────────────────────────────────
    public function destroy(User $user): \Illuminate\Http\JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Kendinizi silemezsiniz.'], 422);
        }

        $this->abortIfOutOfScope($user);

        $user->delete();

        return response()->json(['success' => true, 'message' => 'Kullanıcı silindi.']);
    }

    // ── Scope guard — kullanıcı kapsam dışındaysa 403 ────────────────────────
    private function abortIfOutOfScope(User $target): void
    {
        $me = auth()->user();

        if ($me->hasRole('super-admin')) {
            return;
        }

        // Hedef super-admin'se asla erişilemesin
        if ($target->hasRole('super-admin')) {
            abort(403, 'Bu kullanıcıya erişim yetkiniz yok.');
        }

        if ($me->hasRole(['municipality-admin', 'municipality-staff'])) {
            if ((int) $target->institution_id !== (int) $me->institution_id) {
                abort(403, 'Başka bir kurumun kullanıcısına erişemezsiniz.');
            }
            return;
        }

        // institution-manager / staff
        if ($target->hasRole(['municipality-admin', 'municipality-staff'])) {
            abort(403, 'Bu kullanıcıya erişim yetkiniz yok.');
        }

        if ((int) $target->institution_id !== (int) $me->institution_id) {
            abort(403, 'Başka bir kurumun kullanıcısına erişemezsiniz.');
        }
    }
}
