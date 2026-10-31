<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AzureSsoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->string('status')->default('active');
            $table->string('fspr_number')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        config([
            'services.azure.client_id' => 'test-client-id',
            'services.azure.client_secret' => 'test-client-secret',
            'services.azure.redirect' => 'http://localhost/auth/azure/callback',
            'services.azure.tenant' => 'test-tenant-id',
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_user_can_be_redirected_to_microsoft_sign_in(): void
    {
        Socialite::fake('azure');

        $this->get(route('azure.redirect'))
            ->assertRedirect('https://socialite.fake/azure/authorize');
    }

    public function test_active_existing_user_can_sign_in_with_matching_email(): void
    {
        $user = User::create([
            'name' => 'Advisor',
            'email' => 'advisor@example.com',
            'password' => 'local-password',
            'status' => 'active',
        ]);
        Socialite::fake('azure', SocialiteUser::fake([
            'id' => 'entra-object-id',
            'email' => 'ADVISOR@example.com',
        ]));

        $this->get(route('azure.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_sso_does_not_create_an_account_for_an_unknown_email(): void
    {
        Socialite::fake('azure', SocialiteUser::fake([
            'email' => 'unknown@example.com',
        ]));

        $this->get(route('azure.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_inactive_existing_user_cannot_sign_in_with_sso(): void
    {
        User::create([
            'name' => 'Inactive Advisor',
            'email' => 'inactive@example.com',
            'password' => 'local-password',
            'status' => 'inactive',
        ]);
        Socialite::fake('azure', SocialiteUser::fake([
            'email' => 'inactive@example.com',
        ]));

        $this->get(route('azure.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sso_redirect_fails_closed_when_tenant_configuration_is_missing(): void
    {
        config(['services.azure.tenant' => null]);

        $this->get(route('azure.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_sso_redirect_rejects_a_multi_tenant_authority(): void
    {
        config(['services.azure.tenant' => 'common']);

        $this->get(route('azure.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }
}