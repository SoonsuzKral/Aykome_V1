<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldTask;
use App\Models\FieldTaskMedia;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveMapController extends Controller
{
    /**
     * Yalnızca super-admin ve municipality-admin canlı haritayı görebilir.
     * field-team, institution-staff vb. → 403.
     * checkIn ve updateLocation herkese açık (field-team kullanır).
     */
    public function __construct()
    {
        $this->middleware('can:pro.live_map')->only(['index', 'liveData']);
    }

    /** Admin: Canlı harita ekranı */
    public function index(): View
    {
        $googleMapsApiKey = config('services.google_maps.api_key')
            ?: config('aykome.google_maps_api_key');

        return view('admin.live-map-pro.index', compact('googleMapsApiKey'));
    }

    /**
     * Canlı veri endpoint'i (30 sn polling)
     *
     * CANLI AKTİFLER  : is_on_field=1 AND last_seen_at >= now()-2dk
     *                   (Ping atmadan yeni çıkmışsa: field_started_at >= now()-2dk)
     * SON GÖRÜLENLER  : is_on_field=0 AND last_seen_at >= bugün başlangıcı
     *
     * Zombi güvencesi: is_on_field=1 ama last_seen_at 2 dk'dan eskiyse
     * liveData zaten Canlıya almaz; CheckFieldStaffStatus Job en geç 1 dk'da DB'yi düzeltir.
     */
    public function liveData(): JsonResponse
    {
        /* ── CANLI AKTİFLER ── */
        $cutoff = now()->subMinutes(2);

        $fieldUsers = User::role('field-team')
            ->where('is_on_field', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->where(function ($q) use ($cutoff) {
                /* Son 2 dk içinde ping attı */
                $q->where('last_seen_at', '>=', $cutoff)
                  /* VEYA henüz ping yok ama check-in taze (< 2 dk) */
                  ->orWhere(function ($inner) use ($cutoff) {
                      $inner->whereNull('last_seen_at')
                            ->where('field_started_at', '>=', $cutoff);
                  });
            })
            ->with([
                'fieldTasksAssigned' => fn ($q) => $q
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->with(['application:id,application_no,address_text'])
                    ->orderByDesc('updated_at')
                    ->limit(1),
            ])
            ->get()
            ->map(function (User $user) {
                $activeTask = $user->fieldTasksAssigned->first();

                $taskIds = FieldTask::query()
                    ->where('assigned_to', $user->id)
                    ->pluck('id');

                $recentMedia = FieldTaskMedia::query()
                    ->whereIn('field_task_id', $taskIds)
                    ->orderByDesc('id')
                    ->limit(3)
                    ->get()
                    ->map(fn ($m) => [
                        'thumb' => asset('storage/' . $m->image_path),
                        'full'  => asset('storage/' . $m->image_path),
                    ]);

                $minutesOnField = $user->field_started_at
                    ? (int) $user->field_started_at->diffInMinutes(now())
                    : null;

                return [
                    'id'                 => $user->id,
                    'name'               => $user->name,
                    'initials'           => mb_strtoupper(mb_substr($user->name, 0, 2)),
                    'lat'                => (float) $user->current_lat,
                    'lng'                => (float) $user->current_lng,
                    'field_started_at'   => $user->field_started_at?->format('H:i'),
                    'minutes_on_field'   => $minutesOnField,
                    'active_app_id'      => $activeTask?->application?->id,
                    'active_app_no'      => $activeTask?->application?->application_no,
                    'active_app_address' => $activeTask?->application?->address_text,
                    'active_task_status' => $activeTask?->status,
                    'recent_media'       => $recentMedia,
                    'color_index'        => $user->id % 8,
                ];
            });

        /* ── SON GÖRÜLENLER (bugün sahaya girip ayrıldı) ── */
        $recentUsers = User::role('field-team')
            ->where('is_on_field', false)
            ->whereNotNull('last_seen_lat')
            ->whereNotNull('last_seen_lng')
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', now()->startOfDay())
            ->get()
            ->map(function (User $user) {
                $lastTask = FieldTask::query()
                    ->where('assigned_to', $user->id)
                    ->with(['application:id,application_no,address_text'])
                    ->orderByDesc('updated_at')
                    ->first();

                $taskIds = FieldTask::query()
                    ->where('assigned_to', $user->id)
                    ->pluck('id');

                $lastMedia = FieldTaskMedia::query()
                    ->whereIn('field_task_id', $taskIds)
                    ->orderByDesc('id')
                    ->first();

                $lastActivityText = null;
                if ($lastMedia) {
                    $lastActivityText = $lastMedia->created_at
                        ? $lastMedia->created_at->diffForHumans() . ' fotoğraf ekledi'
                        : 'fotoğraf ekledi';
                } elseif ($lastTask) {
                    $lastActivityText = $lastTask->updated_at
                        ? $lastTask->updated_at->diffForHumans() . ' görev güncelledi'
                        : 'görev güncellendi';
                }

                return [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'initials'         => mb_strtoupper(mb_substr($user->name, 0, 2)),
                    'lat'              => (float) $user->last_seen_lat,
                    'lng'              => (float) $user->last_seen_lng,
                    'last_seen_at'     => $user->last_seen_at?->format('H:i'),
                    'last_seen_diff'   => $user->last_seen_at?->diffForHumans(),
                    'last_app_id'      => $lastTask?->application?->id,
                    'last_app_no'      => $lastTask?->application?->application_no,
                    'last_app_address' => $lastTask?->application?->address_text,
                    'last_activity'    => $lastActivityText,
                    'color_index'      => $user->id % 8,
                ];
            });

        return response()->json([
            'users'        => $fieldUsers,
            'recent_users' => $recentUsers,
            'updated_at'   => now()->format('H:i:s'),
            'total'        => $fieldUsers->count(),
            'total_recent' => $recentUsers->count(),
        ]);
    }

    /**
     * Saha personeli: Check-in / Check-out toggle
     */
    public function checkIn(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'is_on_field' => ['required', 'boolean'],
            'lat'         => ['nullable', 'numeric', 'between:-90,90'],
            'lng'         => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $goingOnField = (bool) $validated['is_on_field'];

        $updates = [
            'is_on_field'      => $goingOnField,
            'current_lat'      => $goingOnField ? ($validated['lat'] ?? null) : null,
            'current_lng'      => $goingOnField ? ($validated['lng'] ?? null) : null,
            'field_started_at' => $goingOnField ? now() : null,
        ];

        if (!$goingOnField) {
            if ($user->current_lat && $user->current_lng) {
                $updates['last_seen_lat'] = $user->current_lat;
                $updates['last_seen_lng'] = $user->current_lng;
            }
            $updates['last_seen_at'] = now();
        }

        $user->update($updates);

        return response()->json([
            'success'     => true,
            'is_on_field' => $goingOnField,
            'message'     => $goingOnField
                ? 'Mesainiz başladı. Konumunuz merkeze iletiliyor.'
                : 'Sahadan ayrıldınız. İyi çalışmalar!',
        ]);
    }

    /**
     * Saha personeli: Konum güncelle (arka plan GPS ping)
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $user = auth()->user();

        if ($user->is_on_field) {
            $user->update([
                'current_lat'  => $validated['lat'],
                'current_lng'  => $validated['lng'],
                'last_seen_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
