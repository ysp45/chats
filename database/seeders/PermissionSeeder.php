<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        Permission::create(['name' => 'chat-all']);
        Permission::create(['name' => 'chat-specific']);
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $psychologistRole = Role::create(['name' => 'psychologist']);
        $userRole = Role::create(['name' => 'user']);
        
        // Assign permissions to roles
        $adminRole->givePermissionTo('chat-all');
        $psychologistRole->givePermissionTo('chat-all');
        $userRole->givePermissionTo('chat-specific');
        
        // Assign roles to users (example)
        // You would typically do this in a separate seeder or when creating users
        // $admin = User::find(1);
        // $admin->assignRole('admin');
        
        // $psychologist = User::find(2);
        // $psychologist->assignRole('psychologist');
        
        // $user = User::find(3);
        // $user->assignRole('user');
    }
}