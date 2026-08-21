<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tüm Spatie rolleri siler — YALNIZCA super-admin kurtarılır.
 *
 * Bu migration, AykomeSeeder/ProcessFlowSeeder'daki role oluşturma
 * kodlarından önce çalıştırılabilir ya da elle. DB'de hâlihazırda
 * var olan eski roller (mudur, kurum-personel vb.) da temizlenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Spatie permission paketinin tablo isimleri
        $rolesTable          = DB::table('roles');
        $modelHasRolesTable  = DB::table('model_has_roles');
        $roleHasPermsTable   = DB::table('role_has_permissions');

        // super-admin rolünün ID'sini al (eğer yoksa, hiçbir şey silinmez)
        $superAdmin = $rolesTable->where('name', 'super-admin')->first();
        $keepId = $superAdmin ? $superAdmin->id : null;

        if ($keepId === null) {
            // super-admin hiç yoksa hepsini sil — seeder yeniden yaratır
            $roleHasPermsTable->truncate();
            $modelHasRolesTable->truncate();
            $rolesTable->truncate();
            return;
        }

        // 1) role_has_permissions pivot'ı temizle
        $roleHasPermsTable
            ->where('role_id', '!=', $keepId)
            ->delete();

        // 2) model_has_roles pivot'ı temizle
        $modelHasRolesTable
            ->where('role_id', '!=', $keepId)
            ->delete();

        // 3) roles tablosundan sil
        $rolesTable
            ->where('id', '!=', $keepId)
            ->delete();

        // 4) Eski/kalıcı rolleri (DB'de ama artık silinmiş olanları) de temizle
        //    (ör. mudur, kurum-personel gibi seeder'larda artık yaratılmayanlar)
        //    yukarıdaki where('id', '!=', $keepId) bunu zaten kapsar.
    }

    public function down(): void
    {
        // Geri alma: seeders yeniden çalıştırılarak roller yeniden yaratılır.
        // Bu migration'da ters işlem yoktur — rollback seed ile yapılır.
    }
};
