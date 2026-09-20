<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarayRemovalSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_admin_dashboard_renders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_staff_dashboard_renders(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk();
    }

    public function test_resident_dashboard_renders(): void
    {
        $resident = User::factory()->create(['role' => 'resident']);
        $this->actingAs($resident)->get(route('resident.dashboard'))->assertOk();
    }

    public function test_users_table_has_no_locale_column(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('users', 'locale'));
    }
}
