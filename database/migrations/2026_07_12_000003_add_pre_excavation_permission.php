<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Permission::query()->firstOrCreate([
            'name' => 'applications.approve_pre_excavation',
            'guard_name' => 'web',
        ]);

        // Assign to super-admin
        $superAdmin = Role::query()->where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo('applications.approve_pre_excavation');
        }

        // Assign to municipality roles
        foreach (['municipality-admin', 'municipality-staff'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo('applications.approve_pre_excavation');
            }
        }
    }

    public function down(): void
    {
        Permission::query()->where('name', 'applications.approve_pre_excavation')->delete();
    }
};
