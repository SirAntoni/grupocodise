<?php

it('redirige la raíz al panel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/panel');
});

it('un usuario desactivado pierde acceso', function () {
    $user = App\Models\User::factory()->create(['is_active' => false]);

    $response = $this->actingAs($user)->get('/panel');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
