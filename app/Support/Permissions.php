<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Permissions
{
    public const ALL = [
        'members.manage',
        'plans.manage',
        'subscriptions.manage',
        'payments.manage',
        'checkins.manage',
        'classes.manage',
        'reports.view',
        'gym.manage',
        'staff.manage',
    ];

    public const STAFF = [
        'members.manage',
        'subscriptions.manage',
        'payments.manage',
        'checkins.manage',
        'classes.manage',
        'reports.view',
    ];

    public static function sync(): void
    {
        foreach (self::ALL as $permission) {
            Permission::findOrCreate($permission);
        }

        $owner = Role::findOrCreate('owner');
        $owner->syncPermissions(self::ALL);

        $staff = Role::findOrCreate('staff');
        $staff->syncPermissions(self::STAFF);
    }
}
