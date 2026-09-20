<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTrendChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_with_bar_chart_config(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee("type: 'bar'", false);
        $response->assertDontSee("type: 'line'", false);
        $response->assertSee('trendChart', false);

        // ApexCharts throws at construction time for bar/column charts when
        // tooltip.shared is true without tooltip.intersect explicitly false
        // (bar charts default intersect to true, unlike line charts) — this
        // previously broke both dashboard charts silently in the browser.
        $response->assertSee('shared: true, intersect: false', false);

        // Mobile breakpoint override so the 12 month labels stay legible
        // instead of ApexCharts auto-rotating them into unreadable slivers.
        $response->assertSee('responsive:', false);
    }

    public function test_recent_activity_view_all_links_to_audit_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('audit.index'), false);
        $response->assertDontSee('<span class="text-xs font-medium text-green-700 hover:underline cursor-pointer">View all</span>', false);
    }

    public function test_monthly_trends_json_endpoint_still_works(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard.monthly-trends'));

        $response->assertOk();
        $response->assertJsonStructure(['labels', 'documentsIssued', 'complaints', 'newResidents']);
    }
}
