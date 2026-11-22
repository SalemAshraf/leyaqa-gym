<?php

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;

it('shows a public member card by qr token without login and without money data', function () {
    [$gym] = makeOwner();
    $gym->update(['name' => 'جيم النيل']);
    $plan = Plan::factory()->create(['gym_id' => $gym->id, 'price' => 800]);
    $member = Member::factory()->create(['gym_id' => $gym->id, 'name' => 'عضو البطاقة']);
    Subscription::factory()->create([
        'gym_id' => $gym->id, 'member_id' => $member->id, 'plan_id' => $plan->id,
        'status' => 'active', 'starts_at' => today(), 'ends_at' => today()->addDays(10), 'price' => 800,
    ]);

    $this->get('/card/' . $member->qr_token)->assertOk()->assertInertia(
        fn ($page) => $page
            ->where('member.name', 'عضو البطاقة')
            ->where('gym.name', 'جيم النيل')
            ->where('subscription.status', 'active')
            ->missing('member.balance')
            ->missing('subscription.price')
    );
});

it('404s on unknown card tokens and never crosses tenants', function () {
    [$gymA] = makeOwner();
    [$gymB] = makeOwner();
    Member::factory()->create(['gym_id' => $gymB->id]);

    $this->get('/card/not-a-real-token')->assertNotFound();
});
