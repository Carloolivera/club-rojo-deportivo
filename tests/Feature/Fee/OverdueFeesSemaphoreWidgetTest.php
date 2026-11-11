<?php

namespace Tests\Feature\Fee;

use App\Filament\Widgets\OverdueFeesSemaphoreWidget;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueFeesSemaphoreWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_widget_shows_green_when_no_overdue(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $data = $this->getWidgetData();

        $this->assertEquals(0, $data['count']);
        $this->assertEquals('ok', $data['status']);
    }

    public function test_widget_shows_warning_when_few_overdue(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->createOverdueFees(3);
        $this->actingAs($admin);

        $data = $this->getWidgetData();

        $this->assertEquals(3, $data['count']);
        $this->assertEquals('warning', $data['status']);
    }

    public function test_widget_shows_danger_when_many_overdue(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->createOverdueFees(6);
        $this->actingAs($admin);

        $data = $this->getWidgetData();

        $this->assertEquals(6, $data['count']);
        $this->assertEquals('danger', $data['status']);
    }

    public function test_widget_not_visible_to_authority(): void
    {
        $authority = User::factory()->create();
        $authority->assignRole('authority');

        $this->actingAs($authority);

        $this->assertFalse(OverdueFeesSemaphoreWidget::canView());
    }

    public function test_widget_visible_to_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->assertTrue(OverdueFeesSemaphoreWidget::canView());
    }

    public function test_widget_visible_to_manager(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $this->assertTrue(OverdueFeesSemaphoreWidget::canView());
    }

    private function createOverdueFees(int $count): void
    {
        $sport = Sport::factory()->create();
        $category = FeeCategory::factory()->create();
        $period = now()->subMonths(2)->format('Y-m');

        for ($i = 0; $i < $count; $i++) {
            $member = Member::factory()->create([
                'sport_id' => $sport->id,
                'fee_category_id' => $category->id,
                'status' => 'active',
            ]);
            Fee::factory()->create([
                'member_id' => $member->id,
                'status' => 'pending',
                'period' => $period,
            ]);
        }
    }

    private function getWidgetData(): array
    {
        $widget = new OverdueFeesSemaphoreWidget();
        return $widget->getData();
    }

    private function seedRoles(): void
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'authority', 'guard_name' => 'web']);
    }
}
