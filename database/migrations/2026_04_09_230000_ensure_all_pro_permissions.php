<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

/**
 * 6 adet satılabilir PRO yetkisini güvenli şekilde oluşturur.
 * firstOrCreate kullanıldığından mevcut kayıtları bozmaz.
 */
return new class extends Migration
{
    private array $proPerms = [
        'pro.live_map'         => 'Canlı Saha İzleme Paneli',
        'pro.field_tracking'   => 'Saha Check-in / Mesai Takibi',
        'pro.work_orders'      => 'Görev Emri Yönetimi & Kanban',
        'pro.advanced_reports' => 'Gelişmiş Rapor Motoru',
        'pro.field_reports'    => 'Gelişmiş Saha Personel Raporu',
        'pro.evrak_tevdi'      => 'Evrak ve Tevdi (E-Belge)',
    ];

    public function up(): void
    {
        foreach (array_keys($this->proPerms) as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // super-admin her zaman tüm yetkilere sahip olmalı
        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(
                Permission::whereIn('name', array_keys($this->proPerms))->get()
            );
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys($this->proPerms))->delete();
    }
};
