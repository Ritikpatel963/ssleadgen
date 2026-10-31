<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePermission;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LookupValue;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeadEditModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_sources');
        Schema::dropIfExists('lookup_values');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('insurers');

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->json('permissions')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->string('name')->default('Admin');
            $table->string('email')->unique();
            $table->string('password')->default('secret');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lookup_values', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('estimated_cover', 12, 2)->nullable();
            $table->foreignId('lead_source_id')->nullable();
            $table->string('status')->default('new');
            $table->foreignId('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('policy_no')->nullable();
            $table->string('company')->nullable();
            $table->date('login_date')->nullable();
            $table->decimal('anp', 12, 2)->nullable();
            $table->date('dob')->nullable();
            $table->string('address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('city')->nullable();
            $table->string('post_code')->nullable();
            $table->string('adviser')->nullable();
            $table->string('not_counting')->nullable();
            $table->string('compliance_by')->nullable();
            $table->date('roa_due_date')->nullable();
            $table->string('status_compliance')->nullable();
            $table->string('sent_to_client')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamps();
        });
    }

    public function test_lead_show_endpoint_returns_json_for_edit_modal(): void
    {
        $source = LeadSource::create(['name' => 'Meta Ads']);

        $lead = Lead::create([
            'first_name' => 'Madhukant',
            'last_name' => 'Patel',
            'phone' => '279588765',
            'email' => 'madhukant@example.com',
            'lead_source_id' => $source->id,
            'policy_no' => 'POL-9921',
            'company' => 'AIA Health',
            'login_date' => '2026-08-15',
            'anp' => 4500,
            'dob' => '1985-04-12',
            'address' => '42 Queen Street',
            'suburb' => 'Central',
            'city' => 'Auckland',
            'post_code' => '1010',
            'adviser' => 'Sushant Yadav',
            'not_counting' => 'No',
            'compliance_by' => 'Officer Sarah',
            'roa_due_date' => '2026-09-01',
            'status_compliance' => 'Sent to Compliance',
            'sent_to_client' => 'Pending',
            'outcome' => 'Awaiting medical review',
            'estimated_cover' => 6500,
            'status' => 'new',
        ]);

        $admin = new User(['status' => 'active']);

        $response = $this->withoutMiddleware(RequirePermission::class)
            ->actingAs($admin)
            ->get(route('crm.leads.show', $lead->id), ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('lead.id', $lead->id)
            ->assertJsonPath('lead.first_name', 'Madhukant')
            ->assertJsonPath('lead.last_name', 'Patel')
            ->assertJsonPath('lead.full_name', 'Madhukant Patel')
            ->assertJsonPath('lead.phone', '279588765')
            ->assertJsonPath('lead.email', 'madhukant@example.com')
            ->assertJsonPath('lead.lead_source', 'Meta Ads')
            ->assertJsonPath('lead.policy_no', 'POL-9921')
            ->assertJsonPath('lead.company', 'AIA Health')
            ->assertJsonPath('lead.login_date', '2026-08-15')
            ->assertJsonPath('lead.anp', 4500)
            ->assertJsonPath('lead.dob', '1985-04-12')
            ->assertJsonPath('lead.address', '42 Queen Street')
            ->assertJsonPath('lead.suburb', 'Central')
            ->assertJsonPath('lead.city', 'Auckland')
            ->assertJsonPath('lead.post_code', '1010')
            ->assertJsonPath('lead.adviser', 'Sushant Yadav')
            ->assertJsonPath('lead.status_compliance', 'Sent to Compliance')
            ->assertJsonPath('lead.outcome', 'Awaiting medical review');
    }

    public function test_lead_update_endpoint_validates_required_fields(): void
    {
        $lead = Lead::create([
            'first_name' => 'Original',
            'last_name' => 'Lead',
            'phone' => '12345678',
        ]);

        $admin = new User(['status' => 'active']);

        $response = $this->withoutMiddleware(RequirePermission::class)
            ->actingAs($admin)
            ->patch(route('crm.leads.update', $lead->id), [
                'first_name' => '',
                'phone' => '',
            ], ['Accept' => 'application/json']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'phone']);
    }

    public function test_lead_update_endpoint_successfully_updates_lead(): void
    {
        $lead = Lead::create([
            'first_name' => 'Initial',
            'last_name' => 'Person',
            'phone' => '021000000',
            'status' => 'new',
        ]);

        $admin = new User(['status' => 'active']);

        $payload = [
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'phone' => '021999888',
            'email' => 'updated@example.com',
            'policy_no' => 'POL-NEW-77',
            'company' => 'Chubb Life',
            'login_date' => '2026-09-10',
            'anp' => '5200',
            'dob' => '1992-06-20',
            'address' => '88 High Street',
            'suburb' => 'Epsom',
            'city' => 'Auckland',
            'post_code' => '1023',
            'adviser' => 'Sushant Yadav',
            'not_counting' => 'Yes',
            'compliance_by' => 'Officer Mike',
            'roa_due_date' => '2026-10-01',
            'status_compliance' => 'Approved',
            'sent_to_client' => 'Yes',
            'outcome' => 'Policy active',
            'lead_source' => 'Website Inbound',
            'estimated_cover' => 7000,
            'status' => 'contacted',
        ];

        $response = $this->withoutMiddleware(RequirePermission::class)
            ->actingAs($admin)
            ->patch(route('crm.leads.update', $lead->id), $payload, ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('lead.first_name', 'UpdatedFirst')
            ->assertJsonPath('lead.policy_no', 'POL-NEW-77');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'phone' => '021999888',
            'policy_no' => 'POL-NEW-77',
            'company' => 'Chubb Life',
            'status' => 'contacted',
            'status_compliance' => 'Approved',
        ]);

        $this->assertDatabaseHas('lead_sources', [
            'name' => 'Website Inbound',
        ]);
    }

    public function test_pipeline_view_contains_edit_lead_modal_and_options(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_super_admin' => true]);
        $admin = User::create(['name' => 'Admin User', 'email' => 'admin@example.com', 'role_id' => $role->id, 'status' => 'active']);

        $lead = Lead::create([
            'first_name' => 'Madhukant',
            'last_name' => 'Patel',
            'phone' => '279588765',
            'status' => 'new',
        ]);

        $response = $this->withoutMiddleware(RequirePermission::class)
            ->actingAs($admin)
            ->get(route('crm.pipeline'));

        $response->assertOk()
            ->assertSee('id="editLeadModal"', false)
            ->assertSee('id="editLeadForm"', false)
            ->assertSee('editLeadFromDetailModal()', false)
            ->assertSee('openEditLeadModal(' . $lead->id . ')', false)
            ->assertSee('Update Lead Record', false);
    }
}
