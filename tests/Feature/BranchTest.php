<?php

use App\Models\Branch;
use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permissions::sync();
});

function branchGym(): array
{
    $gym = Gym::factory()->create(['platform_status' => 'active']);
    $owner = User::factory()->create(['gym_id' => $gym->id]);
    $owner->assignRole(Role::findByName('owner'));

    return [$gym, $owner];
}

it('lets the owner manage branches', function () {
    [$gym, $owner] = branchGym();

    $this->actingAs($owner)->post('/branches', ['name' => 'فرع مدينة نصر'])->assertSessionHasNoErrors();
    expect(Branch::where('gym_id', $gym->id)->where('name', 'فرع مدينة نصر')->count())->toBe(1);

    $branch = Branch::first();
    $this->actingAs($owner)->put("/branches/{$branch->id}", ['name' => 'فرع المعادي'])->assertSessionHasNoErrors();
    expect($branch->fresh()->name)->toBe('فرع المعادي');

    $this->actingAs($owner)->delete("/branches/{$branch->id}")->assertSessionHasNoErrors();
    expect(Branch::count())->toBe(0);
});

it('rejects duplicate branch names inside one gym but allows them across gyms', function () {
    [$gym, $owner] = branchGym();
    Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'الرئيسي']);

    $this->actingAs($owner)->post('/branches', ['name' => 'الرئيسي'])->assertSessionHasErrors('name');

    [$gym2, $owner2] = branchGym();
    $this->actingAs($owner2)->post('/branches', ['name' => 'الرئيسي'])->assertSessionHasNoErrors();
});

it('never lets a gym touch another gym\'s branch', function () {
    [$gym, $owner] = branchGym();
    $branch = Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'فرع']);

    [$gym2, $owner2] = branchGym();
    $this->actingAs($owner2)->put("/branches/{$branch->id}", ['name' => 'مخترق'])->assertNotFound();
    $this->actingAs($owner2)->delete("/branches/{$branch->id}")->assertNotFound();
    expect($branch->fresh()->name)->toBe('فرع');
});

it('refuses to delete a branch that still has members', function () {
    [$gym, $owner] = branchGym();
    $branch = Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'فرع']);
    Member::factory()->create(['gym_id' => $gym->id, 'branch_id' => $branch->id, 'name' => 'عضو', 'phone' => '010']);

    $this->actingAs($owner)->delete("/branches/{$branch->id}")->assertSessionHasErrors('branch');
    expect(Branch::count())->toBe(1);
});

it('scopes branch-pinned staff to their branch and keeps the owner global', function () {
    [$gym, $owner] = branchGym();
    $a = Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'أ']);
    $b = Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'ب']);

    $memberA = Member::factory()->create(['gym_id' => $gym->id, 'branch_id' => $a->id, 'name' => 'عضو الفرع أ', 'phone' => '011']);
    $memberB = Member::factory()->create(['gym_id' => $gym->id, 'branch_id' => $b->id, 'name' => 'عضو الفرع ب', 'phone' => '012']);

    // A member with no branch is NOT exposed to branch-pinned staff either.
    $memberFree = Member::factory()->create(['gym_id' => $gym->id, 'branch_id' => null, 'name' => 'عضو بلا فرع', 'phone' => '014']);

    $staff = User::factory()->create(['gym_id' => $gym->id, 'branch_id' => $a->id]);
    $staff->assignRole(Role::findByName('staff'));

    // Staff sees only branch A members in the list and search.
    $this->actingAs($staff)->get('/members')
        ->assertInertia(fn ($page) => $page->has('members.data', 1));
    $this->actingAs($staff)->get('/members/search?q=عضو')
        ->assertJsonCount(1);

    // Unassigned member is invisible to pinned staff, visible to the owner.
    $this->actingAs($staff)->get("/members/{$memberFree->id}")->assertNotFound();
    $this->actingAs($owner)->get("/members/{$memberFree->id}")->assertOk();

    // Staff cannot open or edit the other branch's member.
    $this->actingAs($staff)->get("/members/{$memberB->id}")->assertNotFound();
    $this->actingAs($staff)->put("/members/{$memberB->id}", [
        'name' => 'مخترق', 'phone' => '012', 'status' => 'active',
    ])->assertNotFound();
    expect($memberB->fresh()->name)->toBe('عضو الفرع ب');

    // Owner still sees and edits everyone.
    $this->actingAs($owner)->get("/members/{$memberB->id}")->assertOk();
    $this->actingAs($owner)->get('/members')
        ->assertInertia(fn ($page) => $page->has('members.data', 3));
});

it('blocks branch-pinned staff from checking in another branch\'s member', function () {
    [$gym, $owner] = branchGym();
    $a = Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'أ']);
    $b = Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'ب']);
    $memberB = Member::factory()->create(['gym_id' => $gym->id, 'branch_id' => $b->id, 'name' => 'عضو ب', 'phone' => '012']);

    $staff = User::factory()->create(['gym_id' => $gym->id, 'branch_id' => $a->id]);
    $staff->assignRole(Role::findByName('staff'));

    $this->actingAs($staff)->post('/check-in', ['qr_token' => $memberB->qr_token])->assertNotFound();

    $memberFree = Member::factory()->create(['gym_id' => $gym->id, 'branch_id' => null, 'name' => 'عضو بلا فرع', 'phone' => '015']);
    $this->actingAs($staff)->post('/check-in', ['qr_token' => $memberFree->qr_token])->assertNotFound();
    expect(CheckIn::count())->toBe(0);

    // Owner check-in records the member's branch on the log row.
    $this->actingAs($owner)->post('/check-in', ['qr_token' => $memberB->qr_token])->assertSessionHasNoErrors();
    expect(CheckIn::first()->branch_id)->toBe($b->id);
});

it('assigns members and staff to branches with same-gym validation', function () {
    [$gym, $owner] = branchGym();
    $a = Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'أ']);

    $this->actingAs($owner)->post('/members', [
        'name' => 'عضو جديد', 'phone' => '013', 'branch_id' => $a->id,
    ])->assertSessionHasNoErrors();
    expect(Member::firstWhere('name', 'عضو جديد')->branch_id)->toBe($a->id);

    // A branch from another gym is not a valid assignment.
    [$gym2] = branchGym();
    $foreign = Branch::factory()->create(['gym_id' => $gym2->id, 'name' => 'غريب']);
    $this->actingAs($owner)->post('/members', [
        'name' => 'عضو اخر', 'phone' => '014', 'branch_id' => $foreign->id,
    ])->assertSessionHasErrors('branch_id');

    // Staff creation accepts a same-gym branch.
    $this->actingAs($owner)->post('/staff', [
        'name' => 'موظف', 'email' => 'staff@x.com', 'password' => 'password123',
        'role' => 'staff', 'branch_id' => $a->id,
    ])->assertSessionHasNoErrors();
    expect(User::firstWhere('email', 'staff@x.com')->branch_id)->toBe($a->id);

    // And branch assignment can be updated.
    $staffUser = User::firstWhere('email', 'staff@x.com');
    $this->actingAs($owner)->put("/staff/{$staffUser->id}", ['branch_id' => null])->assertSessionHasNoErrors();
    expect($staffUser->fresh()->branch_id)->toBeNull();
});

it('blocks pinned staff from subscribing or class-booking another branch member', function () {
    [$gym, $owner] = branchGym();
    $a = \App\Models\Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'أ']);
    $b = \App\Models\Branch::factory()->create(['gym_id' => $gym->id, 'name' => 'ب']);
    $memberB = Member::factory()->create(['gym_id' => $gym->id, 'branch_id' => $b->id, 'name' => 'عضو ب', 'phone' => '016']);
    $plan = \App\Models\Plan::factory()->create(['gym_id' => $gym->id]);
    $class = \App\Models\GymClass::factory()->create(['gym_id' => $gym->id]);

    $staff = User::factory()->create(['gym_id' => $gym->id, 'branch_id' => $a->id]);
    $staff->assignRole(Role::findByName('staff'));

    $this->actingAs($staff)->post('/subscriptions', [
        'member_id' => $memberB->id, 'plan_id' => $plan->id, 'starts_at' => now()->toDateString(),
    ])->assertNotFound();

    $this->actingAs($staff)->post("/classes/{$class->id}/bookings", [
        'member_id' => $memberB->id, 'class_date' => now()->addDay()->toDateString(),
    ])->assertNotFound();

    // Same actions by the owner pass the branch guard (may fail later validation, never 404).
    $this->actingAs($owner)->post('/subscriptions', [
        'member_id' => $memberB->id, 'plan_id' => $plan->id, 'starts_at' => now()->toDateString(),
    ])->assertSessionHasNoErrors();
});
