<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_creates_an_admin_with_the_typed_password(): void
    {
        $this->artisan('club:admin', ['email' => 'jefa@clubrojo.demo'])
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'una-clave-larga-123')
            ->assertSuccessful();

        $user = User::where('email', 'jefa@clubrojo.demo')->firstOrFail();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue(Hash::check('una-clave-larga-123', $user->password));
    }

    public function test_rejects_a_short_password(): void
    {
        $this->artisan('club:admin', ['email' => 'jefa@clubrojo.demo'])
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'corta')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'jefa@clubrojo.demo']);
    }
}
