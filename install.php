<?php

/**
 * Payment Gateways Module - Installation Script
 *
 * Runs when the module is installed via TadreebLMS External Apps system.
 *
 * This script:
 *   1. Creates the "manage payment gateways" Spatie permission and assigns to Administrator
 */

use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$modulePath = dirname(__FILE__);

// ---------------------------------------------------------------------------
// 1. Seed Spatie permissions
// ---------------------------------------------------------------------------
try {
    $permissionName = 'manage payment gateways';
    $permission = Permission::firstOrCreate(
        ['name' => $permissionName, 'guard_name' => 'web']
    );

    $adminRole = Role::where('name', 'Administrator')->first();
    if ($adminRole && !$adminRole->hasPermissionTo($permissionName)) {
        $adminRole->givePermissionTo($permission);
    }

    // Clear cached permissions so the new one takes effect
    app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    Log::info('[PaymentGateways] Permission "manage payment gateways" created and assigned to Administrator.');
} catch (\Exception $e) {
    Log::error('[PaymentGateways] Failed to seed permissions: ' . $e->getMessage());
}

// Install complete – no echo to avoid corrupting JSON response
