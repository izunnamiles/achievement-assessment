<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => 'correct-password',
    ]);
});

test('a user can log in with valid credentials and receive a jwt', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'correct-password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'access_token', 'expires_in']);
});

test('a user cannot log in with invalid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);
});
