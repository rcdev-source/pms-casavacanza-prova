<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('authenticates an active demo user', function (): void {
    $this->seed(DatabaseSeeder::class);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.test',
        'password' => 'Password123!',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.user.email', 'admin@example.test')
        ->assertJsonStructure(['data' => ['token']]);

    expect(User::query()->firstOrFail()->tokens)->toHaveCount(1);
});

it('rejects invalid credentials', function (): void {
    $this->seed(DatabaseSeeder::class);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.test',
        'password' => 'wrong-password',
    ])->assertUnprocessable();
});

it('rejects a disabled user', function (): void {
    $this->seed(DatabaseSeeder::class);
    User::query()->where('email', 'admin@example.test')->update(['disabled_at' => now()]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.test',
        'password' => 'Password123!',
    ])->assertForbidden();
});
