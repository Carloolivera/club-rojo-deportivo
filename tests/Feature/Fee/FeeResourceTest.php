<?php

namespace Tests\Feature\Fee;

use App\Filament\Resources\FeeResource;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeeResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function managerUser(Sport $sport): User
    {
        $user = User::factory()->create(['sport_id' => $sport->id]);
        $user->assignRole('manager');
        return $user;
    }

    private function createFee(array $attributes = []): Fee
    {
        $category = FeeCategory::factory()->create(['amount' => 3000]);
        $sport    = Sport::factory()->create();
        $member   = Member::factory()->create([
            'status'          => 'active',
            'fee_category_id' => $category->id,
            'sport_id'        => $sport->id,
        ]);
        return Fee::factory()->create(array_merge([
            'member_id' => $member->id,
            'period'    => '2026-04',
            'amount'    => 3000,
            'status'    => 'pending',
        ], $attributes));
    }

    public function test_admin_can_list_all_fees(): void
    {
        $admin = $this->adminUser();
        $this->createFee();
        $this->createFee();

        $this->actingAs($admin);

        Livewire::test(FeeResource\Pages\ListFees::class)
            ->assertCanSeeTableRecords(Fee::all());
    }

    public function test_manager_only_sees_fees_of_their_sport(): void
    {
        $sport1  = Sport::factory()->create();
        $sport2  = Sport::factory()->create();
        $manager = $this->managerUser($sport1);

        $cat = FeeCategory::factory()->create(['amount' => 3000]);

        $member1 = Member::factory()->create(['sport_id' => $sport1->id, 'fee_category_id' => $cat->id, 'status' => 'active']);
        $member2 = Member::factory()->create(['sport_id' => $sport2->id, 'fee_category_id' => $cat->id, 'status' => 'active']);

        $fee1 = Fee::factory()->create(['member_id' => $member1->id, 'period' => '2026-04', 'amount' => 3000, 'status' => 'pending']);
        $fee2 = Fee::factory()->create(['member_id' => $member2->id, 'period' => '2026-04', 'amount' => 3000, 'status' => 'pending']);

        $this->actingAs($manager);

        Livewire::test(FeeResource\Pages\ListFees::class)
            ->assertCanSeeTableRecords([$fee1])
            ->assertCanNotSeeTableRecords([$fee2]);
    }

    public function test_admin_can_mark_fee_as_paid_manually(): void
    {
        $admin = $this->adminUser();
        $fee   = $this->createFee(['status' => 'pending']);

        $this->actingAs($admin);

        Livewire::test(FeeResource\Pages\ListFees::class)
            ->callTableAction('mark_paid', $fee);

        $fee->refresh();
        $this->assertEquals('paid', $fee->status);
        $this->assertEquals('manual', $fee->payment_method);
        $this->assertNotNull($fee->paid_at);
    }
}
