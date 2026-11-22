<?php

it('defaults coach commission to off and zero, owing nothing', function () {
    [$gym] = makeOwner();
    expect((bool) $gym->fresh()->commission_enabled)->toBeFalse()
        ->and((float) $gym->fresh()->pt_commission_percent)->toBe(0.0);
});

it('lets the owner store a reversible commission rate', function () {
    [$gym, $owner] = makeOwner();

    $this->actingAs($owner)->patch('/settings/commission', ['enabled' => true, 'pt_percent' => 12.5])
        ->assertSessionHasNoErrors();

    expect((bool) $gym->fresh()->commission_enabled)->toBeTrue()
        ->and((float) $gym->fresh()->pt_commission_percent)->toBe(12.5);

    // Fully reversible.
    $this->actingAs($owner)->patch('/settings/commission', ['enabled' => false, 'pt_percent' => 0])
        ->assertSessionHasNoErrors();
    expect((bool) $gym->fresh()->commission_enabled)->toBeFalse()
        ->and((float) $gym->fresh()->pt_commission_percent)->toBe(0.0);
});

it('validates the commission rate bounds', function () {
    [$gym, $owner] = makeOwner();
    $this->actingAs($owner)->patch('/settings/commission', ['enabled' => true, 'pt_percent' => 101])
        ->assertSessionHasErrors('pt_percent');
    $this->actingAs($owner)->patch('/settings/commission', ['enabled' => true, 'pt_percent' => -1])
        ->assertSessionHasErrors('pt_percent');
});
