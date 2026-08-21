<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

echo "=== TÜM ROLER ===\n";
foreach (Role::all() as $r) {
    echo $r->name . ' | users:' . $r->users()->count() . ' | perms:' . $r->permissions()->count() . "\n";
}

echo "\n=== MODEL_HAS_ROLES (kullanıcı atamaları) ===\n";
$assignments = DB::table('model_has_roles')
    ->join('users', 'model_id', '=', 'users.id')
    ->select('users.name', 'users.email', 'role_id', 'model_type')
    ->get();
foreach ($assignments as $row) {
    $role = Role::find($row->role_id);
    $shortType = str_replace('\\', '', $row->model_type);
    echo ($row->name ?? 'N/A') . ' (' . ($row->email ?? 'N/A') . ') -> role:' . ($role ? $role->name : 'NULL') . ' | type:' . $shortType . "\n";
}

echo "\n=== ROLE_PERMISSION MAP ===\n";
foreach (Role::all() as $r) {
    $perms = $r->permissions()->pluck('name')->toArray();
    echo $r->name . ' => ' . implode(', ', $perms) . "\n";
}

echo "\n=== TOPLAMA ===\n";
echo "Toplam rol: " . Role::count() . "\n";
echo "Toplam permission: " . Permission::count() . "\n";
