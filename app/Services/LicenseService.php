<?php

namespace App\Services;

use App\Models\Application;
use App\Models\License;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class LicenseService
{
    /**
     * Oturum açmış kullanıcı için geçerli bir lisans var mı?
     * Domain/IP kullanılmaz; yalnızca veritabanı.
     */
    public function passesForUser(User $user): bool
    {
        $license = $this->resolveApplicableLicense($user);

        return $license !== null && $this->isUserCountWithinLimit($license);
    }

    /**
     * Kurulumda herhangi bir geçerli (aktif tarih aralığında) lisans tanımlı mı?
     * Komutlar veya sistem kontrolleri için.
     */
    public function isSystemLicensed(): bool
    {
        return License::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', now());
            })
            ->whereDate('valid_until', '>=', now()->toDateString())
            ->exists();
    }

    /**
     * Kullanıcıya uygulanabilir ilk lisans (kurum özeli > genel).
     */
    public function resolveApplicableLicense(User $user): ?License
    {
        $query = License::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', now());
            })
            ->whereDate('valid_until', '>=', now()->toDateString());

        if ($user->institution_id) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('institution_id')
                    ->orWhere('institution_id', $user->institution_id);
            });
        } else {
            $query->whereNull('institution_id');
        }

        return $query
            ->orderByRaw('institution_id IS NULL ASC')
            ->orderByDesc('id')
            ->first();
    }

    public function isUserCountWithinLimit(?License $license): bool
    {
        if ($license === null || $license->user_limit === null) {
            return true;
        }

        $count = User::query()->where('is_active', true)->count();

        return $count <= (int) $license->user_limit;
    }

    /**
     * @param  list<string>|null  $required
     */
    public function licenseAllowsModules(?License $license, ?array $required): bool
    {
        if ($license === null) {
            return false;
        }

        $modules = $license->modules;
        if ($modules === null || $modules === []) {
            return true;
        }

        if ($required === null || $required === []) {
            return true;
        }

        foreach ($required as $key) {
            if (! in_array($key, $modules, true)) {
                return false;
            }
        }

        return true;
    }

    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return License::query()->orderByDesc('id')->paginate($perPage);
    }

    public function store(array $data): License
    {
        return License::query()->create($data);
    }

    public function update(License $license, array $data): License
    {
        $license->update($data);

        return $license->fresh();
    }

    /**
     * @return array{path: string, filename: string}
     */
    public function generateExcavationPermitPdf(Application $application): array
    {
        $applicationNo = $application->application_no;
        if ($applicationNo === null || $applicationNo === '') {
            throw new \InvalidArgumentException('Başvuru numarası olmadan ruhsat üretilemez.');
        }

        $application->load([
            'institution',
            'creator',
            'excavationAreas',
            'surfaceLines.surfaceType',
            'priceApprover',
            'receiptApprover',
        ]);

        $pdf = Pdf::loadView('admin.pdf.ruhsat', [
            'application' => $application,
        ])->setPaper('a4', 'portrait');

        $filename = 'ruhsat-'.$applicationNo.'.pdf';
        $path = 'licenses/'.$application->id.'/'.$filename;
        Storage::disk('local')->put($path, $pdf->output());

        return ['path' => $path, 'filename' => $filename];
    }
}
