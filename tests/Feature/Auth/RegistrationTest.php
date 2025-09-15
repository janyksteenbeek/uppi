<?php

use App\Filament\Pages\Auth\Register;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('registration page can be rendered', function () {
    $this->get('/register')
        ->assertSuccessful();
});

test('new users can register', function () {
    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'passwordPassword',
            'passwordConfirmation' => 'passwordPassword',
            'timezone' => 'UTC',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'timezone' => 'UTC',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect(Hash::check('passwordPassword', $user->password))->toBeTrue();

    $this->assertAuthenticated();
});

test('email must be unique', function () {
    User::factory()->create([
        'email' => 'test@example.com',
    ]);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->call('register')
        ->assertHasFormErrors(['email' => 'unique']);
});

test('password must be confirmed', function () {
    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])
        ->call('register')
        ->assertHasFormErrors(['password']);

    assertDatabaseMissing('users', [
        'email' => 'test@example.com',
    ]);
});

test('email must be valid format', function () {
    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->call('register')
        ->assertHasFormErrors(['email']);

    assertDatabaseMissing('users', [
        'email' => 'not-an-email',
    ]);
});

test('name is required', function () {
    Livewire::test(Register::class)
        ->fillForm([
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->call('register')
        ->assertHasFormErrors(['name']);

    assertDatabaseMissing('users', [
        'email' => 'test@example.com',
    ]);
});

test('timezone must be valid', function () {
    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'timezone' => '',
        ])
        ->call('register')
        ->assertHasFormErrors(['timezone']);

    assertDatabaseMissing('users', [
        'email' => 'test@example.com',
    ]);
});
