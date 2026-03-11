<?php

/**
 * Payment Gateways Module - Uninstallation Script
 *
 * Runs when the module is uninstalled via TadreebLMS External Apps system.
 *
 * This script:
 *   1. Removes the "manage payment gateways" Spatie permission
 */

use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

// ---------------------------------------------------------------------------
// 1. Remove Spatie permission
// ---------------------------------------------------------------------------
try {
    $permission = Permission::where('name', 'manage payment gateways')
        ->where('guard_name', 'web')
        ->first();

    if ($permission) {
        // Detach from all roles first
        $permission->roles()->detach();
        $permission->delete();
    }

    // Clear cached permissions
    app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    Log::info('[PaymentGateways] Permission "manage payment gateways" removed.');
} catch (\Exception $e) {
    Log::error('[PaymentGateways] Failed to remove permissions: ' . $e->getMessage());
}

// Uninstall complete – no echo to avoid corrupting JSON response
