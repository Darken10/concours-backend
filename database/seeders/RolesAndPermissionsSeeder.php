<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create roles
        $userRole = Role::create(['name' => UserRoleEnum::USER->value]);
        $adminRole = Role::create(['name' => UserRoleEnum::ADMIN->value]);
        $superAdminRole = Role::create(['name' => UserRoleEnum::SUPER_ADMIN->value]);
        $agentRole = Role::create(['name' => UserRoleEnum::AGENT->value]);

        // create permissions (example)
        // Permission::create(['name' => 'edit articles']);

        // assign permissions to roles
        // $adminRole->givePermissionTo(Permission::all());
    }
}
