<?php

use App\Models\Gym;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

function makeOwner(?Gym $gym = null): array
{
    Permissions::sync();
    $gym ??= Gym::factory()->create();
    $user = User::factory()->create(['gym_id' => $gym->id, 'password' => Hash::make('password')]);
    $user->assignRole(Role::findByName('owner'));

    return [$gym, $user];
}

function makeStaff(Gym $gym): User
{
    Permissions::sync();
    $user = User::factory()->create(['gym_id' => $gym->id, 'password' => Hash::make('password')]);
    $user->assignRole(Role::findByName('staff'));

    return $user;
}
