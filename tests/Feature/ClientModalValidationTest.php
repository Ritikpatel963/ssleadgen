<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePermission;
use App\Http\Controllers\Crm\ClientController;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClientModalValidationTest extends TestCase
{
    public function test_login_client_validation_errors_are_returned_as_json_for_the_modal(): void
    {
        $this->withoutMiddleware(RequirePermission::class)
            ->actingAs(new User(['status' => 'active']))
            ->post(route('clients.login.store'), [], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'phone'])
            ->assertJsonMissingValidationErrors(['email']);
    }

    public function test_login_client_records_with_duplicate_policy_phone_and_email_are_saved_and_listed(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_super_admin')->default(false);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
        });

        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id');
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('policy_no')->nullable();
            $table->string('company')->nullable();
            $table->string('address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('city')->nullable();
            $table->string('post_code')->nullable();
            $table->date('dob')->nullable();
            $table->date('login_date')->nullable();
            $table->decimal('anp', 10, 2)->nullable();
            $table->string('outcome')->nullable();
            $table->string('adviser')->nullable();
            $table->boolean('not_counting')->default(false);
            $table->string('compliance_by')->nullable();
            $table->date('roa_due_date')->nullable();
            $table->string('status_compliance')->nullable();
            $table->string('sent_to_client')->nullable();
            $table->string('claim_admin')->nullable();
            $table->string('npw_admin')->nullable();
            $table->string('canc_admin')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        $this->withoutMiddleware(RequirePermission::class)
            ->actingAs(new User(['status' => 'active']));

        $clientDetails = [
            'policy_no' => 'DUPLICATE-POLICY',
            'first_name' => 'Taylor',
            'last_name' => 'Example',
            'phone' => '021123456',
            'email' => 'taylor@example.com',
        ];

        $this->post(route('clients.login.store'), $clientDetails, ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->post(route('clients.login.store'), $clientDetails, ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('clients', 2);

        $this->post(route('clients.login.data'), [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_inforce_request_requires_its_starred_date_on_the_server(): void
    {
        try {
            app(ClientController::class)->markAsInforce(
                Request::create('/clients/login/1/inforce', 'POST', [
                    'client_name' => 'Taylor Example',
                    'inforce_request_type' => 'LOA',
                ]),
                new Client()
            );
            $this->fail('The request should fail validation without its required date.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('inforce_date', $exception->errors());
        }
    }

    public function test_npw_deferred_requires_its_starred_issue_date_and_premium_on_the_server(): void
    {
        try {
            app(ClientController::class)->markAsNpwDeferred(
                Request::create('/clients/login/1/npw-deferred', 'POST', [
                    'client_name' => 'Taylor Example',
                ]),
                new Client()
            );
            $this->fail('The request should fail validation without the required NPW details.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('npw_issue_date', $exception->errors());
            $this->assertArrayHasKey('npw_premium', $exception->errors());
        }
    }

    public function test_claim_update_requires_its_starred_client_name_on_the_server(): void
    {
        try {
            app(ClientController::class)->markAsClaim(
                Request::create('/clients/login/1/claim', 'POST', [
                    'claim_type' => 'Medical claim',
                ]),
                new Client()
            );
            $this->fail('The request should fail validation without its required client name.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('client_name', $exception->errors());
        }
    }
}
