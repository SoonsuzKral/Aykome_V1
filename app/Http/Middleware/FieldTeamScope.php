<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dinamik yetki kapısı — statik "izin verilen rotalar" listesi kaldırıldı.
 *
 * Mantık: Rotanın middleware yığınında (controller middleware dahil) bir
 * permission/role gate varsa kullanıcının o anahtara sahip olup olmadığına
 * bakar. Anahtarı varsa ROL'ÜNE BAKMAKSIZIN geçirir, yoksa 403 döndürür.
 * Hiçbir gate bulunamazsa rota "açık" kabul edilerek geçirilir (dashboard,
 * profil, bildirimler vb.).
 *
 * Bu sayede yeni her PRO modül eklendiğinde bu dosyaya dokunmak gerekmez;
 * ilgili controller ya da route üzerindeki can:/permission: middleware'i
 * yeterlidir.
 */
class FieldTeamScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Giriş yapmamış kullanıcılar doğrudan geçer
        if (! $user) {
            return $next($request);
        }

        // Dashboard her zaman açık — giriş yapmış TÜM kullanıcılar için muaf.
        // Bu satır field-team kontrolünden ÖNCE gelmelidir; aksi hâlde
        // /admin → redirect(admin.dashboard) → middleware → redirect → sonsuz döngü oluşur.
        if ($request->routeIs('admin.dashboard')) {
            return $next($request);
        }

        // field-team rolü olmayanlar doğrudan geçer (belediye, kurum, super-admin vb.)
        if (! $user->hasRole('field-team')) {
            return $next($request);
        }

        // /admin veya /admin/ bare URL → dashboard yönlendirmesi (login sonrası iniş noktası)
        if ($request->is('admin') || $request->is('admin/')) {
            return redirect()->route('admin.dashboard');
        }

        // Rotanın tam middleware yığınını tara (controller middleware dahil)
        foreach ($request->route()?->gatherMiddleware() ?? [] as $mw) {

            // Laravel Gate: can:yetki_adı
            if (preg_match('/^can:([^,\s]+)/', $mw, $m)) {
                return $user->can(trim($m[1]))
                    ? $next($request)
                    : abort(403, 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
            }

            // Spatie Permission: permission:yetki_adı
            if (preg_match('/^permission:([^|\s]+)/', $mw, $m)) {
                return $user->can(trim($m[1]))
                    ? $next($request)
                    : abort(403, 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
            }

            // Spatie Role: role:rol_adı — field-team bu rolde değilse engelle
            if (preg_match('/^role:(\S+)/', $mw, $m)) {
                return $user->hasRole(trim($m[1]))
                    ? $next($request)
                    : abort(403, 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
            }
        }

        // Hiçbir permission/role gate bulunamadı → açık rota (dashboard, profil, field.checkin vb.)
        return $next($request);
    }
}
