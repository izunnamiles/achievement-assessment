<?php

use App\Models\User;

test('a user can log in with valid credentials and receive a jwt', function () {
    $user = User::factory()->create([
        'password' => 'correct-password',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'access_token', 'expires_in']);
});

test('a user cannot log in with invalid credentials', function () {
    $user = User::factory()->create([
        'password' => 'correct-password',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);
});

