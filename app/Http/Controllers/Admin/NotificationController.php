<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'read' => (bool) $n->read_at,
                'title' => $n->data['title'] ?? 'Bildirim',
                'message' => $n->data['message'] ?? '',
                'url' => $n->data['url'] ?? '#',
                'type' => $n->data['type'] ?? 'info',
                'created_at' => $n->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $id = $request->input('id');

        if ($id) {
            $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        } else {
            $request->user()->unreadNotifications->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Tüm bildirimler okundu olarak işaretlendi.');
    }
}
