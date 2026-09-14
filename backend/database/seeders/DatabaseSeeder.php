<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            Role::ADMIN => 'Amministratore',
            Role::RECEPTION => 'Reception',
            Role::CLEANING => 'Pulizie',
            Role::MAINTENANCE => 'Manutenzione',
        ])->mapWithKeys(fn (string $name, string $key): array => [
            $key => Role::query()->firstOrCreate(['key' => $key], ['name' => $name]),
        ]);

        $users = [
            ['email' => 'admin@example.test', 'name' => 'Admin Demo', 'role' => Role::ADMIN],
            ['email' => 'reception@example.test', 'name' => 'Reception Demo', 'role' => Role::RECEPTION],
            ['email' => 'cleaning@example.test', 'name' => 'Cleaning Demo', 'role' => Role::CLEANING],
            ['email' => 'maintenance@example.test', 'name' => 'Maintenance Demo', 'role' => Role::MAINTENANCE],
        ];

        foreach ($users as $item) {
            $user = User::query()->firstOrCreate(
                ['email' => $item['email']],
                ['name' => $item['name'], 'password' => Hash::make('Password123!'), 'email_verified_at' => now()],
            );
            $user->roles()->syncWithoutDetaching([$roles[$item['role']]->id]);
        }
    }
}
