<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePermission;
use App\Models\Client;
use App\Models\ClientServiceRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClientServiceRequestWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('client_service_requests');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->string('adviser')->nullable();
            $table->string('status');
            $table->date('inforce_date')->nullable();
            $table->string('inforce_request_type')->nullable();
            $table->string('inforce_process_status')->nullable();
            $table->string('inforce_process_by')->nullable();
            $table->text('inforce_outcome')->nullable();
            $table->text('inforce_comments')->nullable();
            $table->date('inforce_finished_date')->nullable();
            $table->timestamps();
        });
        Schema::create('client_service_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->date('request_date');
            $table->string('client_name');
            $table->string('company')->nullable();
            $table->string('request_type');
            $table->string('process_status')->nullable();
            $table->string('process_by')->nullable();
            $table->text('outcome')->nullable();
            $table->text('comments')->nullable();
            $table->date('finished_date')->nullable();
            $table->string('status')->default('Pending');
            $table->timestamps();
        });
    }

    public function test_login_client_request_is_pending_without_changing_client_stage(): void
    {
        $client = $this->createLoginClient();

        $this->requestAsUser()->postJson(route('clients.login.requests.store', $client), $this->requestData())
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('client_service_requests', [
            'client_id' => $client->id,
            'request_type' => 'LOA',
            'status' => 'Pending',
        ]);
        $this->assertSame('Login Client', $client->fresh()->status);
    }

    public function test_inforce_client_request_is_visible_without_changing_client_stage(): void
    {
        $client = $this->createLoginClient();
        $client->update(['status' => 'Inforce']);

        $this->requestAsUser()->postJson(route('clients.inforce.requests.store', $client), $this->requestData())
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('client_service_requests', [
            'client_id' => $client->id,
            'request_type' => 'LOA',
            'status' => 'Pending',
        ]);
        $this->assertSame('Inforce', $client->fresh()->status);
    }

    public function test_editing_a_request_preserves_its_client_and_stage(): void
    {
        $client = $this->createLoginClient();
        $serviceRequest = $this->createRequest($client);

        $this->requestAsUser()->patchJson(route('clients.requests.update', $serviceRequest), $this->requestData([
            'inforce_request_type' => 'Address update',
        ]))->assertOk();

        $this->assertDatabaseHas('client_service_requests', [
            'id' => $serviceRequest->id,
            'client_id' => $client->id,
            'request_type' => 'Address update',
            'status' => 'Pending',
        ]);
        $this->assertSame('Login Client', $client->fresh()->status);
    }

    public function test_resolving_a_request_does_not_move_the_client(): void
    {
        $client = $this->createLoginClient();
        $serviceRequest = $this->createRequest($client);

        $this->requestAsUser()->postJson(route('clients.requests.resolve', $serviceRequest))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('Resolved', $serviceRequest->fresh()->status);
        $this->assertSame('Login Client', $client->fresh()->status);
    }

    public function test_explicit_move_to_inforce_saves_request_details_and_changes_stage(): void
    {
        $client = $this->createLoginClient();
        $serviceRequest = $this->createRequest($client);

        $this->requestAsUser()->postJson(route('clients.requests.move-to-inforce', $serviceRequest), $this->requestData([
            'inforce_process_status' => 'Completed',
        ]))->assertOk();

        $this->assertSame('Inforce', $client->fresh()->status);
        $this->assertSame('LOA', $client->fresh()->inforce_request_type);
        $this->assertSame('Completed', $client->fresh()->inforce_process_status);
        $this->assertSame('Pending', $serviceRequest->fresh()->status);
    }

    public function test_login_client_edit_form_can_save_fields_and_move_client_to_inforce(): void
    {
        $client = $this->createLoginClient();

        $this->requestAsUser()->postJson(route('clients.login.move-to-inforce', $client), [
            'first_name' => 'Taylor Updated',
            'last_name' => 'Example',
            'phone' => '0211234567',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'first_name' => 'Taylor Updated',
            'phone' => '0211234567',
            'status' => 'Inforce',
        ]);
    }

    private function createLoginClient(): Client
    {
        return Client::create([
            'first_name' => 'Taylor',
            'last_name' => 'Example',
            'status' => 'Login Client',
        ]);
    }

    private function createRequest(Client $client): ClientServiceRequest
    {
        return ClientServiceRequest::create([
            'client_id' => $client->id,
            'request_date' => '2026-10-07',
            'client_name' => 'Taylor Example',
            'request_type' => 'LOA',
            'status' => 'Pending',
        ]);
    }

    private function requestData(array $overrides = []): array
    {
        return array_merge([
            'inforce_date' => '2026-10-07',
            'client_name' => 'Taylor Example',
            'company' => 'Example Insurance',
            'inforce_request_type' => 'LOA',
            'inforce_process_status' => 'In Processing',
            'inforce_process_by' => 'Demo Adviser',
            'inforce_outcome' => 'Request received',
            'inforce_comments' => 'Please process this request.',
            'inforce_finished_date' => '2026-10-08',
        ], $overrides);
    }

    private function requestAsUser()
    {
        return $this->withoutMiddleware(RequirePermission::class)
            ->actingAs(new User(['status' => 'active']));
    }
}