<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VillaUser extends Pivot
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_STAFF = 'staff';

    protected $table = 'villa_users';
}
