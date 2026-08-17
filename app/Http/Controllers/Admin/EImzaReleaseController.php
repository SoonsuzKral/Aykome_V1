<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * ÇÖZÜM_09 §3 — E-İmza masaüstü uygulaması sürüm yönetimi.
 *
 * Dosyalar `storage/app/public/downloads/eimza/` altında tutulur ve
 * `public/storage` symlink'i üzerinden STATİK olarak sunulur:
 *
 *   <panel>/storage/downloads/eimza/latest.yml   ← electron-updater feed
 *   <panel>/storage/downloads/eimza/AykomeEImzaSetup-1.0.0.exe
 *   <panel>/storage/downloads/eimza/AykomeEImzaSetup-1.0.0.exe.blockmap
 *
 * Neden PHP controller'dan değil, statik yoldan?
 *   electron-updater fark (differential) indirmesi için HTTP Range isteği
 *   gönderir; statik sunum bunu destekler, PHP stream response desteklemez.
 *
 * ⚠️ Bu klasör kimlik doğrulaması OLMADAN erişilebilir — masaüstü uygulamasının
 *    oturumu yoktur, güncellemeyi böyle almak zorundadır. İçeriğe yalnızca
 *    kurulum paketleri (imzalanmış .exe + manifest) konur; gizli veri konmaz.
 *    Dosya adları SAFE_NAME deseniyle sınırlandırılır (traversal önlemi).
 */
class EImzaReleaseController extends Controller
{
    /** public disk üzerindeki yayın klasörü. */
    public const DIR = 'downloads/eimza';

    /** electron-builder'ın ürettiği manifest adı (generic provider, channel=latest). */
    public const MANIFEST = 'latest.yml';

    /** Statik sunulacağı için dosya adları ASCII ve sade olmak zorunda. */
    private const SAFE_NAME = '/^[A-Za-z0-9][A-Za-z0-9._-]{0,119}$/';

    /** Navbar'ın her istekte disk taramaması için (store/destroy düşürür). */
    private const CACHE_KEY = 'eimza.release.has_setup';

    public function index()
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        return view('admin.e-imza.releases', [
            'files'      => self::files(),
            'manifest'   => self::manifest(),
            'health'     => self::health(),
            'feedUrl'    => rtrim(config('app.url'), '/') . '/storage/' . self::DIR . '/',
            'phpLimits'  => [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size'       => ini_get('post_max_size'),
            ],
        ]);
    }

    /**
     * Yeni sürüm yükle. electron-builder çıktısındaki ÜÇ dosya birlikte
     * yüklenmelidir (`dist/` klasöründen):
     *   AykomeEImzaSetup-<sürüm>.exe            → zorunlu
     *   latest.yml                              → otomatik güncelleme için gerekli
     *   AykomeEImzaSetup-<sürüm>.exe.blockmap   → fark indirmesi için (opsiyonel)
     *
     * Yükleme SIRASINDA tutarlılık denetlenir: manifest'teki `path` yüklenen
     * .exe adıyla, `sha512` de dosyanın gerçek özetiyle aynı olmalıdır. Aksi
     * halde electron-updater istemcide "404" ya da "sha512 mismatch" ile
     * patlar — bunu sunucuda yakalamak, sahadaki kurulumlarda aramaktan iyidir.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $request->validate([
            'setup'    => ['required', 'file', 'max:307200'],  // 300 MB
            'manifest' => ['nullable', 'file', 'max:64'],      // latest.yml (~1 KB)
            'blockmap' => ['nullable', 'file', 'max:51200'],   // 50 MB
        ], [], [
            'setup'    => 'kurulum dosyası (.exe)',
            'manifest' => 'manifest (latest.yml)',
            'blockmap' => 'blockmap dosyası',
        ]);

        $setup    = $request->file('setup');
        $manifest = $request->file('manifest');
        $blockmap = $request->file('blockmap');

        $exeName = self::safeName($setup->getClientOriginalName(), 'exe');

        $manifestBody = null;
        if ($manifest) {
            if (strtolower($manifest->getClientOriginalExtension()) !== 'yml') {
                throw ValidationException::withMessages([
                    'manifest' => 'Manifest dosyası .yml uzantılı olmalı (electron-builder: latest.yml).',
                ]);
            }

            $manifestBody = (string) file_get_contents($manifest->getRealPath());
            $parsed = self::parseManifest($manifestBody);

            if (($parsed['path'] ?? null) && $parsed['path'] !== $exeName) {
                throw ValidationException::withMessages([
                    'manifest' => "latest.yml içindeki dosya adı ({$parsed['path']}) yüklenen kurulum dosyasıyla "
                        . "({$exeName}) aynı değil. Aynı derlemenin dist/ klasöründeki dosyalarını yükleyin; "
                        . 'dosya adlarını değiştirmeyin.',
                ]);
            }

            $realSha = base64_encode((string) hash_file('sha512', $setup->getRealPath(), true));
            if (($parsed['sha512'] ?? null) && $parsed['sha512'] !== $realSha) {
                throw ValidationException::withMessages([
                    'manifest' => 'latest.yml içindeki sha512 özeti yüklenen .exe ile uyuşmuyor. '
                        . 'Dosyalar farklı derlemelerden geliyor olabilir — dist/ klasörünü yeniden derleyip '
                        . 'üç dosyayı birlikte yükleyin.',
                ]);
            }
        }

        $disk = Storage::disk('public');

        // Yeni bir .exe geldiğinde eski kurulum paketleri temizlenir (yer kaplamasın);
        // latest.yml YALNIZCA yenisi yüklendiyse değiştirilir.
        foreach (self::files() as $f) {
            if (str_ends_with($f['name'], '.exe') || str_ends_with($f['name'], '.blockmap')) {
                $disk->delete(self::DIR . '/' . $f['name']);
            }
        }

        $disk->putFileAs(self::DIR, $setup, $exeName);

        if ($blockmap) {
            $disk->putFileAs(self::DIR, $blockmap, $exeName . '.blockmap');
        }

        if ($manifestBody !== null) {
            $disk->put(self::DIR . '/' . self::MANIFEST, $manifestBody);
        }

        self::forgetCache();

        $version = self::manifest()['version'] ?? null;

        AuditLogger::log(
            'e-imza.release_uploaded',
            'E-İmza masaüstü sürümü yüklendi: ' . $exeName . ($version ? " (v{$version})" : ''),
            'EImzaRelease',
            null,
            [
                'exe'      => $exeName,
                'version'  => $version,
                'manifest' => (bool) $manifestBody,
                'blockmap' => (bool) $blockmap,
                'size'     => $setup->getSize(),
            ],
        );

        $health = self::health();

        return back()->with(
            $health['ok'] ? 'success' : 'warning',
            $health['ok']
                ? 'Yeni sürüm yayınlandı: ' . $exeName . ($version ? " (v{$version})" : '') . '. Otomatik güncelleme hazır.'
                : 'Dosyalar kaydedildi, ancak otomatik güncelleme eksik: ' . implode(' ', $health['issues']),
        );
    }

    /** Tek bir yayın dosyasını sil (yalnızca izinli desene uyan adlar). */
    public function destroy(Request $request)
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $name = basename((string) $request->input('name', ''));

        if (! preg_match(self::SAFE_NAME, $name) || ! self::isReleaseFile($name)) {
            throw ValidationException::withMessages(['name' => 'Geçersiz dosya adı.']);
        }

        $path = self::DIR . '/' . $name;
        abort_unless(Storage::disk('public')->exists($path), 404);

        Storage::disk('public')->delete($path);
        self::forgetCache();

        AuditLogger::log('e-imza.release_deleted', 'E-İmza yayın dosyası silindi: ' . $name, 'EImzaRelease');

        return back()->with('success', $name . ' silindi.');
    }

    /**
     * Header'daki "⬇️ E-İmza İndir" butonu. Content-Disposition: attachment
     * (Storage::download bunu kendisi ekler) — tarayıcı .exe'yi açmaya
     * çalışmaz, doğrudan indirir.
     */
    public function download()
    {
        $exe = self::currentExe();

        if (! $exe) {
            return back()->with('error', 'E-İmza kurulum dosyası henüz yüklenmemiş. '
                . 'Süper Admin → E-İmza Sürüm Yönetimi ekranından yükleyebilirsiniz.');
        }

        $version = self::manifest()['version'] ?? null;
        $nice = $version ? "Aykome-EImza-Setup-{$version}.exe" : $exe;

        AuditLogger::log('e-imza.setup_downloaded', 'E-İmza kurulum dosyası indirildi: ' . $exe, 'EImzaRelease');

        return Storage::disk('public')->download(self::DIR . '/' . $exe, $nice);
    }

    // ── Yardımcılar (navbar/blade tarafından da kullanılır) ──────────────────

    private static function isReleaseFile(string $name): bool
    {
        return $name === self::MANIFEST
            || str_ends_with($name, '.exe')
            || str_ends_with($name, '.exe.blockmap');
    }

    /** @return array<int, array{name:string,size:int,modified:int}> */
    public static function files(): array
    {
        $disk = Storage::disk('public');

        if (! $disk->exists(self::DIR)) {
            return [];
        }

        $out = [];
        foreach ($disk->files(self::DIR) as $path) {
            $name = basename($path);
            if ($name === '.gitignore' || ! self::isReleaseFile($name)) {
                continue;
            }
            $out[] = [
                'name'     => $name,
                'size'     => $disk->size($path),
                'modified' => $disk->lastModified($path),
            ];
        }

        usort($out, fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $out;
    }

    /**
     * latest.yml'i ayrıştır. electron-builder'ın çıktısı düz bir YAML'dır;
     * ihtiyacımız olan alanlar hep üst seviyededir, bu yüzden satır-başı
     * çapalı regex yeterli (symfony/yaml bağımlılığı eklemeye gerek yok).
     *
     * @return array{version:?string,path:?string,sha512:?string,releaseDate:?string}|null
     */
    public static function manifest(): ?array
    {
        $disk = Storage::disk('public');
        $path = self::DIR . '/' . self::MANIFEST;

        if (! $disk->exists($path)) {
            return null;
        }

        return self::parseManifest((string) $disk->get($path));
    }

    /** @return array{version:?string,path:?string,sha512:?string,releaseDate:?string} */
    private static function parseManifest(string $body): array
    {
        $grab = function (string $key) use ($body): ?string {
            // ^key: — girintili (files: altındaki) aynı adlı alanlar eşleşmez.
            if (preg_match('/^' . preg_quote($key, '/') . ':\s*(.+)$/m', $body, $m)) {
                return trim($m[1], " \t\r\n\"'");
            }

            return null;
        };

        return [
            'version'     => $grab('version'),
            'path'        => $grab('path'),
            'sha512'      => $grab('sha512'),
            'releaseDate' => $grab('releaseDate'),
        ];
    }

    /**
     * Yayınlanmış kurulum dosyası. Manifest varsa onun işaret ettiği dosya
     * (istemcinin indireceği dosya ile aynı olsun), yoksa en yeni .exe.
     */
    public static function currentExe(): ?string
    {
        $files = self::files();
        $names = array_column($files, 'name');

        $fromManifest = self::manifest()['path'] ?? null;
        if ($fromManifest && in_array($fromManifest, $names, true)) {
            return $fromManifest;
        }

        foreach ($files as $f) {
            if (str_ends_with($f['name'], '.exe')) {
                return $f['name'];
            }
        }

        return null;
    }

    /**
     * Navbar butonu yalnızca gerçekten indirilebilir bir dosya varsa gösterilir.
     * Her sayfa yükünde disk taramamak için önbelleklenir; yükleme/silme
     * işlemleri önbelleği düşürür.
     */
    public static function hasSetup(): bool
    {
        return (bool) Cache::remember(self::CACHE_KEY, now()->addHours(6), fn () => self::currentExe() !== null);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Otomatik güncellemenin sahada çalışıp çalışacağını sunucuda denetler.
     *
     * @return array{ok:bool, issues:array<int,string>}
     */
    public static function health(): array
    {
        $issues = [];
        $names = array_column(self::files(), 'name');
        $exe = self::currentExe();
        $manifest = self::manifest();

        if (! $exe) {
            $issues[] = 'Kurulum dosyası (.exe) yüklenmemiş.';
        }

        if (! $manifest) {
            $issues[] = 'latest.yml yüklenmemiş — masaüstü uygulaması güncellemeyi göremez.';
        } elseif (($manifest['path'] ?? null) && ! in_array($manifest['path'], $names, true)) {
            $issues[] = 'latest.yml, sunucuda bulunmayan bir dosyayı gösteriyor (' . $manifest['path'] . ').';
        }

        if ($exe && ! in_array($exe . '.blockmap', $names, true)) {
            $issues[] = 'blockmap dosyası yok — güncelleme çalışır ama fark indirmesi yapılamaz (tam indirme).';
        }

        // public/storage bağlantısı: Windows'ta is_link()/is_dir() junction ve
        // symlink'lerde yanlış sonuç verebiliyor (PHP stat sınırlaması), bu yüzden
        // TİP değil ERİŞİM denetlenir — masaüstü uygulamasının okuyacağı yolun
        // gerçekten okunabilir olması tek anlamlı ölçüt.
        $publicRoot = public_path('storage');
        if (! file_exists($publicRoot)) {
            $issues[] = 'public/storage bağlantısı yok — "php artisan storage:link" çalıştırın.';
        } elseif ($manifest && ! @is_readable($publicRoot . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, self::DIR) . DIRECTORY_SEPARATOR . self::MANIFEST)) {
            $issues[] = 'latest.yml public/storage üzerinden okunamıyor — bağlantı bozuk olabilir '
                . '("php artisan storage:link" ile yenileyin).';
        }

        return [
            // blockmap eksikliği güncellemeyi engellemez; sadece uyarıdır.
            'ok'     => $exe !== null && $manifest !== null
                && (! ($manifest['path'] ?? null) || in_array($manifest['path'], $names, true)),
            'issues' => $issues,
        ];
    }

    /** Statik sunulan bir klasöre yazıldığı için dosya adı katı biçimde süzülür. */
    private static function safeName(string $raw, string $forceExt): string
    {
        $base = basename(str_replace('\\', '/', $raw));
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?? '';
        $base = ltrim($base, '.-');

        if (! str_ends_with(strtolower($base), '.' . $forceExt)) {
            $base = pathinfo($base, PATHINFO_FILENAME) . '.' . $forceExt;
        }

        if (! preg_match(self::SAFE_NAME, $base)) {
            $base = 'AykomeEImzaSetup.' . $forceExt;
        }

        return $base;
    }
}
