<?php

namespace Tests\Unit;

use App\Http\Middleware\RequirePermission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    public function test_role_permission_levels_are_enforced(): void
    {
        $user = $this->userWithPermissions(['clients_claims' => 'read']);

        $this->assertTrue($user->canRead('clients_claims'));
        $this->assertFalse($user->canWrite('clients_claims'));
        $this->assertTrue($user->canRead('clients_profile'));
        $this->assertFalse($user->canWrite('clients_profile'));
        $this->assertFalse($user->canRead('unknown_module'));

        $writer = $this->userWithPermissions(['clients_claims' => 'write']);
        $this->assertTrue($writer->canRead('clients_claims'));
        $this->assertTrue($writer->canWrite('clients_claims'));
    }

    public function test_sidebar_modules_are_available_in_the_role_editor(): void
    {
        $modules = Role::modules();

        foreach ([
            'dashboard', 'clients', 'clients_login', 'clients_inforce', 'clients_inactive',
            'clients_claims', 'clients_cancellation', 'clients_npw_deferred', 'leads',
            'tasks', 'calendar', 'policies', 'reports', 'documents', 'access',
        ] as $module) {
            $this->assertContains($module, $modules);
        }
    }

    public function test_sidebar_pages_and_write_routes_use_their_matching_permissions(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            'clients.inforce' => 'clients_inforce',
            'clients.inactive' => 'clients_inactive',
            'clients.claims' => 'clients_claims',
            'clients.cancellation' => 'clients_cancellation',
            'clients.npw-deferred' => 'clients_npw_deferred',
            'clients.profile' => 'clients_profile',
            'clients.login' => 'clients_login',
            'crm.pipeline' => 'leads',
            'crm.reports' => 'reports',
            'policies.index' => 'policies',
        ] as $routeName => $module) {
            $route = $routes->getByName($routeName);
            $this->assertNotNull($route, "Missing route {$routeName}");
            $this->assertContains("permission:{$module},read", $route->gatherMiddleware());
            if (in_array($routeName, [
                'clients.inforce', 'clients.inactive', 'clients.claims',
                'clients.cancellation', 'clients.npw-deferred',
            ], true)) {
                $this->assertSame(substr($routeName, strlen('clients.')), $route->defaults['status']);
            }
        }

        foreach ([
            'appointments.store' => 'calendar',
            'tasks.store' => 'tasks',
            'documents.store' => 'documents',
            'policies.store' => 'policies',
        ] as $routeName => $module) {
            $route = $routes->getByName($routeName);
            $this->assertNotNull($route, "Missing route {$routeName}");
            $this->assertContains("permission:{$module},write", $route->gatherMiddleware());
        }
    }

    public function test_client_status_write_routes_require_the_source_page_write_permission(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            'clients.all.claim' => 'clients',
            'clients.all.inactive' => 'clients',
            'clients.all.npw_deferred' => 'clients',
            'clients.inforce.claim' => 'clients_inforce',
            'clients.inforce.inactive' => 'clients_inforce',
            'clients.inforce.npw_deferred' => 'clients_inforce',
            'clients.inactive.claim' => 'clients_inactive',
            'clients.inactive.inactive' => 'clients_inactive',
            'clients.claims.claim' => 'clients_claims',
            'clients.claims.inactive' => 'clients_claims',
            'clients.cancellation.claim' => 'clients_cancellation',
            'clients.npw-deferred.claim' => 'clients_npw_deferred',
        ] as $routeName => $module) {
            $route = $routes->getByName($routeName);
            $this->assertNotNull($route, "Missing route {$routeName}");
            $this->assertContains("permission:{$module},write", $route->gatherMiddleware());
        }
    }

    public function test_permission_middleware_allows_read_and_blocks_read_only_writes(): void
    {
        $middleware = new RequirePermission();
        $user = $this->userWithPermissions(['documents' => 'read']);
        $next = fn () => response('allowed');

        $read = Request::create('/utilities/documents', 'GET');
        $read->setUserResolver(fn () => $user);
        $this->assertSame(200, $middleware->handle($read, $next, 'documents', 'read')->getStatusCode());

        $write = Request::create('/utilities/documents', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $write->setUserResolver(fn () => $user);
        $this->assertSame(403, $middleware->handle($write, $next, 'documents', 'write')->getStatusCode());
    }

    public function test_users_without_a_role_are_denied(): void
    {
        $user = new User();
        $user->setRelation('role', null);
        $request = Request::create('/settings/access', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => $user);

        $response = (new RequirePermission())->handle($request, fn () => response('allowed'), 'access', 'read');

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_super_admin_role_bypasses_module_permissions(): void
    {
        $user = $this->userWithPermissions([], true);

        $this->assertTrue($user->canWrite('access'));
        $this->assertTrue($user->canWrite('clients_claims'));
    }

    public function test_users_without_dashboard_permission_land_on_an_allowed_sidebar_page(): void
    {
        $user = $this->userWithPermissions(['clients_inforce' => 'read']);

        $this->assertSame('clients.inforce', $user->defaultRouteName());
    }

    private function userWithPermissions(array $permissions, bool $superAdmin = false): User
    {
        $role = new Role(['permissions' => $permissions, 'is_super_admin' => $superAdmin]);
        $user = new User();
        $user->setRelation('role', $role);

        return $user;
    }
}
