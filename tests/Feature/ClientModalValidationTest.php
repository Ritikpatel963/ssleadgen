<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePermission;
use App\Http\Controllers\Crm\ClientController;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
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
