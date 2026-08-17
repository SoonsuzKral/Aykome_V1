<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\EImzaReleaseController as Release;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * ÇÖZÜM_09 §3 — electron-builder çıktısını (dist/) panelin indirme klasörüne
 * kopyalar. Admin panelindeki yükleme formuyla AYNI hedefe yazar.
 *
 * Neden ayrıca CLI?
 *   Kurulum paketi ~85 MB; PHP'nin upload_max_filesize / post_max_size
 *   değerleri çoğu kurulumda bunun altındadır. Sunucuda derliyor/kopyalıyorsanız
 *   HTTP yükleme sınırlarına hiç girmeden yayınlamanın yolu budur.
 *
 * Kullanım:
 *   php artisan eimza:publish
 *   php artisan eimza:publish --dist="C:\Aykome_V1\aykome-e-imza\dist"
 */
class PublishEImzaRelease extends Command
{
    protected $signature = 'eimza:publish
        {--dist= : electron-builder çıktı klasörü (varsayılan: aykome-e-imza/dist)}
        {--keep-old : Sunucudaki eski .exe/.blockmap dosyalarını silme}';

    protected $description = 'E-İmza kurulum paketini (exe + latest.yml + blockmap) panelin indirme klasörüne yayınlar.';

    public function handle(): int
    {
        $dist = $this->option('dist') ?: base_path('aykome-e-imza/dist');

        if (! is_dir($dist)) {
            $this->error("dist klasörü bulunamadı: {$dist}");
            $this->line('Önce derleyin:  cd aykome-e-imza && npm run build:win');

            return self::FAILURE;
        }

        $manifestPath = $dist . DIRECTORY_SEPARATOR . Release::MANIFEST;
        $exePaths = glob($dist . DIRECTORY_SEPARATOR . '*.exe') ?: [];

        if (! $exePaths) {
            $this->error('dist klasöründe .exe bulunamadı.');

            return self::FAILURE;
        }

        // En yeni .exe (birden fazla sürüm kalmış olabilir)
        usort($exePaths, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $exe = $exePaths[0];
        $exeName = basename($exe);

        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,119}$/', $exeName)) {
            $this->error("Kurulum dosyası adı ASCII olmalı: {$exeName}");
            $this->line('package.json > build.nsis.artifactName ayarını kontrol edin.');

            return self::FAILURE;
        }

        return $this->publish($dist, $exe, $exeName, $manifestPath);
    }

    private function publish(string $dist, string $exe, string $exeName, string $manifestPath): int
    {
        $disk = Storage::disk('public');
        $dir = Release::DIR;

        // latest.yml ↔ .exe tutarlılığı (istemcide 404 / sha512 mismatch olmasın)
        if (is_file($manifestPath)) {
            $body = (string) file_get_contents($manifestPath);

            if (preg_match('/^path:\s*(.+)$/m', $body, $m)) {
                $declared = trim($m[1], " \t\r\n\"'");
                if ($declared !== $exeName) {
                    $this->error("latest.yml '{$declared}' dosyasını gösteriyor, kopyalanacak dosya ise '{$exeName}'.");

                    return self::FAILURE;
                }
            }

            if (preg_match('/^sha512:\s*(.+)$/m', $body, $m)) {
                $declared = trim($m[1], " \t\r\n\"'");
                $real = base64_encode((string) hash_file('sha512', $exe, true));
                if ($declared !== $real) {
                    $this->error('latest.yml sha512 özeti .exe ile uyuşmuyor (farklı derlemeler).');

                    return self::FAILURE;
                }
            }
        } else {
            $this->warn('latest.yml bulunamadı — otomatik güncelleme çalışmaz, yalnızca elle indirme olur.');
        }

        if (! $this->option('keep-old')) {
            foreach ($disk->files($dir) as $path) {
                $name = basename($path);
                if (str_ends_with($name, '.exe') || str_ends_with($name, '.blockmap')) {
                    $disk->delete($path);
                }
            }
        }

        $disk->put($dir . '/' . $exeName, fopen($exe, 'rb'));
        $this->info("✓ {$exeName} (" . $this->human(filesize($exe)) . ')');

        $blockmap = $exe . '.blockmap';
        if (is_file($blockmap)) {
            $disk->put($dir . '/' . basename($blockmap), fopen($blockmap, 'rb'));
            $this->info('✓ ' . basename($blockmap));
        }

        if (is_file($manifestPath)) {
            $disk->put($dir . '/' . Release::MANIFEST, (string) file_get_contents($manifestPath));
            $this->info('✓ ' . Release::MANIFEST);
        }

        $this->newLine();
        Release::forgetCache();
        $health = Release::health();
        foreach ($health['issues'] as $issue) {
            $this->warn('! ' . $issue);
        }

        $version = Release::manifest()['version'] ?? '—';
        $this->line('Sürüm       : ' . $version);
        $this->line('Feed adresi : ' . rtrim(config('app.url'), '/') . '/storage/' . $dir . '/');
        $this->line($health['ok'] ? 'Otomatik güncelleme: HAZIR' : 'Otomatik güncelleme: EKSİK');

        return self::SUCCESS;
    }

    private function human(int|false $bytes): string
    {
        $bytes = (int) $bytes;

        return $bytes >= 1048576
            ? round($bytes / 1048576, 2) . ' MB'
            : round($bytes / 1024, 1) . ' KB';
    }
}
