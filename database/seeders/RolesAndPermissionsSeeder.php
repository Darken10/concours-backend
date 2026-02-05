<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\Permission;
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

        // create permissions for posts, categories, and tags
        $editPostsPermission = Permission::create(['name' => 'edit posts']);
        $editCategoriesPermission = Permission::create(['name' => 'edit categories']);
        $editTagsPermission = Permission::create(['name' => 'edit tags']);

        // assign permissions to agent role
        $agentRole->givePermissionTo([
            $editPostsPermission,
            $editCategoriesPermission,
            $editTagsPermission,
        ]);

        // assign all permissions to admin and super-admin roles
        $adminRole->givePermissionTo(Permission::all());
        $superAdminRole->givePermissionTo(Permission::all());
    }
}
