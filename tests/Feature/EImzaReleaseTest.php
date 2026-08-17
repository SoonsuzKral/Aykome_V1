<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\EImzaReleaseController as Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * ÇÖZÜM_09 §3 — E-İmza sürüm yayınlama modülü regresyon testleri.
 *
 * Kritik nokta: latest.yml ile .exe TUTARSIZ olursa sahadaki kurulumlar
 * "404" ya da "sha512 mismatch" alır. Bu yüzden tutarsız yükleme sunucuda
 * reddedilmeli ve HİÇBİR dosya yazılmamalıdır.
 */
class EImzaReleaseTest extends TestCase
{
    use RefreshDatabase;

    private const EXE = 'AykomeEImzaSetup-1.0.1.exe';

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Release::forgetCache();

        Role::findOrCreate('super-admin', 'web');
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');
    }

    /** electron-builder çıktısıyla aynı biçimde (path + sha512) manifest üret. */
    private function manifestFor(string $body, string $exeName = self::EXE): string
    {
        $sha = base64_encode(hash('sha512', $body, true));

        return "version: 1.0.1\n"
            . "files:\n"
            . "  - url: {$exeName}\n"
            . "    sha512: {$sha}\n"
            . '    size: ' . strlen($body) . "\n"
            . "path: {$exeName}\n"
            . "sha512: {$sha}\n"
            . "releaseDate: '2026-08-17T10:00:00.000Z'\n";
    }

    /** @return array{setup: UploadedFile, manifest: UploadedFile, blockmap: UploadedFile} */
    private function validTrio(): array
    {
        $body = str_repeat('MZ-fake-installer-', 64);

        return [
            'setup'    => UploadedFile::fake()->createWithContent(self::EXE, $body),
            'manifest' => UploadedFile::fake()->createWithContent('latest.yml', $this->manifestFor($body)),
            'blockmap' => UploadedFile::fake()->createWithContent(self::EXE . '.blockmap', 'blockmap-bytes'),
        ];
    }

    public function test_index_sadece_super_admin_gorur(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.e-imza-release.index'))
            ->assertOk()
            ->assertSee('E-İmza Sürüm Yönetimi', false);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.e-imza-release.index'))
            ->assertForbidden();
    }

    public function test_gecerli_paket_yayinlanir_ve_feed_hazir_olur(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.e-imza-release.store'), $this->validTrio())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $disk = Storage::disk('public');
        $disk->assertExists(Release::DIR . '/' . self::EXE);
        $disk->assertExists(Release::DIR . '/' . Release::MANIFEST);
        $disk->assertExists(Release::DIR . '/' . self::EXE . '.blockmap');

        $this->assertSame('1.0.1', Release::manifest()['version']);
        $this->assertSame(self::EXE, Release::currentExe());
        $this->assertTrue(Release::health()['ok']);
    }

    public function test_manifest_dosya_adi_uyusmazsa_reddedilir(): void
    {
        $body = str_repeat('x', 128);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.e-imza-release.store'), [
                'setup'    => UploadedFile::fake()->createWithContent(self::EXE, $body),
                // Manifest BASKA bir dosyayi gosteriyor → istemci 404 alirdi.
                'manifest' => UploadedFile::fake()->createWithContent(
                    'latest.yml',
                    $this->manifestFor($body, 'AykomeEImzaSetup-9.9.9.exe'),
                ),
            ])
            ->assertSessionHasErrors('manifest');

        Storage::disk('public')->assertMissing(Release::DIR . '/' . self::EXE);
        Storage::disk('public')->assertMissing(Release::DIR . '/' . Release::MANIFEST);
    }

    public function test_sha512_uyusmazsa_reddedilir(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.e-imza-release.store'), [
                'setup'    => UploadedFile::fake()->createWithContent(self::EXE, 'gercek-icerik'),
                // Manifest FARKLI bir derlemenin ozetini tasiyor.
                'manifest' => UploadedFile::fake()->createWithContent('latest.yml', $this->manifestFor('baska-icerik')),
            ])
            ->assertSessionHasErrors('manifest');

        Storage::disk('public')->assertMissing(Release::DIR . '/' . self::EXE);
    }

    public function test_manifestsiz_yukleme_kabul_edilir_ama_uyarir(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.e-imza-release.store'), [
                'setup' => UploadedFile::fake()->createWithContent(self::EXE, 'icerik'),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('warning');

        Storage::disk('public')->assertExists(Release::DIR . '/' . self::EXE);
        $this->assertFalse(Release::health()['ok']);
        // Elle indirme yine de calisir.
        $this->assertTrue(Release::hasSetup());
    }

    public function test_indirme_attachment_olarak_doner(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.e-imza-release.store'), $this->validTrio());

        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.e-imza-release.download'));

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Aykome-EImza-Setup-1.0.1.exe', (string) $response->headers->get('content-disposition'));
    }

    public function test_dosya_yokken_indirme_hata_mesajiyla_doner(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.e-imza-release.download'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_silme_yol_gecisi_denemesini_reddeder(): void
    {
        $this->actingAs($this->superAdmin)->post(route('admin.e-imza-release.store'), $this->validTrio());

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.e-imza-release.destroy'), ['name' => '../../../.env'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.e-imza-release.destroy'), ['name' => 'not-a-release.txt'])
            ->assertSessionHasErrors('name');

        // Gecerli ad silinebilir.
        $this->actingAs($this->superAdmin)
            ->delete(route('admin.e-imza-release.destroy'), ['name' => self::EXE . '.blockmap'])
            ->assertSessionHas('success');

        Storage::disk('public')->assertMissing(Release::DIR . '/' . self::EXE . '.blockmap');
        Storage::disk('public')->assertExists(Release::DIR . '/' . self::EXE);
    }

    public function test_navbar_butonu_dosya_yokken_gizli(): void
    {
        $this->assertFalse(Release::hasSetup());

        $this->actingAs($this->superAdmin)->post(route('admin.e-imza-release.store'), $this->validTrio());

        $this->assertTrue(Release::hasSetup());
    }
}
