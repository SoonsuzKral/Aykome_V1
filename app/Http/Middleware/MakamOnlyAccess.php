<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Belediye Başkanı (municipality-makam) yalnızca Makam Masası ile çalışır.
 * Başkana gelen onaylar Makam Masası listesinde görünür; diğer tüm admin
 * sayfalarına (başvuru listesi/detay, raporlar, ayarlar vb.) URL ile bile
 * erişemez — Makam Masası'na yönlendirilir. İstisnalar: Dashboard (kendisi
 * zaten Makam Masası'na redirect eder) ve Profil (şifre/hesap).
 */
class MakamOnlyAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('municipality-makam')) {
            return $next($request);
        }

        $route = $request->route();
        $name = $route?->getName() ?? '';

        $allowed = $name === 'admin.dashboard'
            || str_starts_with($name, 'admin.makam.')
            || str_starts_with($name, 'admin.profile.');

        if (! $allowed) {
            return redirect()->route('admin.makam.index');
        }

        return $next($request);
    }
}
