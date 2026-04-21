<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserFeaturePermission;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_disabled_after_first_user_exists(): void
    {
        User::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Second User',
            'email' => 'second@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_create_moderator_with_feature_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'web');

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Moderator One',
            'email' => 'mod1@example.com',
            'role' => 'moderator',
            'password' => 'secretpass1',
            'password_confirmation' => 'secretpass1',
            'permissions' => [
                'users' => ['read' => 1, 'write' => 1],
                'reports' => ['read' => 1],
            ],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'mod1@example.com',
            'role' => 'moderator',
        ]);

        $created = User::where('email', 'mod1@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_feature_permissions', [
            'user_id' => $created->id,
            'feature_key' => 'users',
            'can_read' => 1,
            'can_write' => 1,
        ]);

        $this->assertDatabaseHas('user_feature_permissions', [
            'user_id' => $created->id,
            'feature_key' => 'reports',
            'can_read' => 1,
        ]);
    }

    public function test_moderator_without_users_feature_cannot_access_user_management_pages(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($moderator, 'web');

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.users.create'))->assertForbidden();
    }

    public function test_moderator_with_users_read_can_list_but_cannot_create_users(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        UserFeaturePermission::create([
            'user_id' => $moderator->id,
            'feature_key' => 'users',
            'can_read' => true,
            'can_write' => false,
        ]);

        $this->actingAs($moderator, 'web');

        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.users.create'))->assertForbidden();
    }

    public function test_moderator_sees_existing_admin_data_under_same_tenant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $client = Client::create([
            'user_id' => $admin->id,
            'name' => 'Acme Corp',
            'email' => 'acme@example.com',
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
            'admin_id' => $admin->id,
        ]);

        UserFeaturePermission::create([
            'user_id' => $moderator->id,
            'feature_key' => 'clients',
            'can_read' => true,
            'can_write' => false,
        ]);

        $this->actingAs($moderator, 'web');

        $this->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Acme Corp');
    }

    public function test_users_index_shows_actions_and_admin_can_delete_managed_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $managed = User::factory()->create([
            'role' => 'moderator',
            'admin_id' => $admin->id,
            'email' => 'delete-me@example.com',
        ]);

        $this->actingAs($admin, 'web');

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('View')
            ->assertSee('Edit')
            ->assertSee('Delete');

        $this->delete(route('admin.users.destroy', $managed))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $managed->id,
        ]);
    }

    public function test_admin_can_view_permissions_page_for_managed_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $managed = User::factory()->create([
            'role' => 'moderator',
            'admin_id' => $admin->id,
            'email' => 'view-perms@example.com',
        ]);

        UserFeaturePermission::create([
            'user_id' => $managed->id,
            'feature_key' => 'reports',
            'can_read' => true,
            'can_write' => false,
        ]);

        $this->actingAs($admin, 'web');

        $this->get(route('admin.users.show', $managed))
            ->assertOk()
            ->assertSee('User Permissions')
            ->assertSee('reports');
    }

    public function test_moderator_dashboard_shows_tenant_stats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $currency = \App\Models\Currency::factory()->create([
            'user_id' => $admin->id,
            'is_base' => true,
            'is_active' => true,
            'conversion_rate' => 1,
        ]);

        \App\Models\Client::create([
            'user_id' => $admin->id,
            'name' => 'Dash Client',
        ]);

        \App\Models\Employee::create([
            'user_id' => $admin->id,
            'currency_id' => $currency->id,
            'name' => 'Dash Emp',
            'email' => 'dash-emp@example.com',
            'primary_contact' => '000',
            'role' => 'Dev',
            'employment_type' => 'Onsite',
            'salary' => 100,
        ]);

        \App\Models\Invoice::create([
            'user_id' => $admin->id,
            'currency_id' => $currency->id,
            'invoice_date' => now()->toDateString(),
            'amount' => 100,
            'tax' => 0,
            'status' => 'Pending',
            'approval_status' => 'approved',
            'paid_amount' => 0,
            'remaining_amount' => 100,
        ]);

        $moderator = User::factory()->create([
            'role' => 'moderator',
            'admin_id' => $admin->id,
        ]);

        UserFeaturePermission::create([
            'user_id' => $moderator->id,
            'feature_key' => 'dashboard',
            'can_read' => true,
            'can_write' => false,
        ]);

        $this->actingAs($moderator, 'web');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('1'); // should reflect at least one stat card value
    }

    public function test_sidebar_hides_features_with_no_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $moderator = User::factory()->create([
            'role' => 'moderator',
            'admin_id' => $admin->id,
        ]);

        // Only dashboard is allowed, nothing else.
        UserFeaturePermission::create([
            'user_id' => $moderator->id,
            'feature_key' => 'dashboard',
            'can_read' => true,
            'can_write' => false,
        ]);

        $this->actingAs($moderator, 'web');

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        // Sidebar items should not render when user lacks read/write.
        $response->assertDontSee('Clients</span>', false);
        $response->assertDontSee('Invoices</span>', false);
        $response->assertDontSee('Employees</span>', false);
        $response->assertDontSee('Expenses</span>', false);
        $response->assertDontSee('Bonuses</span>', false);
        $response->assertDontSee('Salary Releases</span>', false);
        $response->assertDontSee('Reports</span>', false);
        $response->assertDontSee('Currency</span>', false);
        $response->assertDontSee('Users</span>', false);
    }
}

