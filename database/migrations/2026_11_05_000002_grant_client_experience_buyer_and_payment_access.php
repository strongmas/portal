<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Roles are created in the admin UI, so this is a no-op on any environment
 * without a role named "Client Experience". Others are set on the Roles screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('admin_roles')->where('slug', 'client-experience')->first();

        if (!$role) {
            return;
        }

        $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

        DB::table('admin_roles')->where('id', $role->id)->update([
            'permissions' => json_encode(array_values(array_unique([...$permissions, 'buyers', 'payments']))),
        ]);
    }

    /** Only "buyers" is removed: the role may already have held "payments". */
    public function down(): void
    {
        $role = DB::table('admin_roles')->where('slug', 'client-experience')->first();

        if (!$role) {
            return;
        }

        $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

        DB::table('admin_roles')->where('id', $role->id)->update([
            'permissions' => json_encode(array_values(array_diff($permissions, ['buyers']))),
        ]);
    }
};
