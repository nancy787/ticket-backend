<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'edit profile']);
        Permission::create(['name' => 'event management']);
        Permission::create(['name' => 'ticket management']);
        Permission::create(['name' => 'user management']);
        Permission::create(['name' => 'configuration']);
    }
}
