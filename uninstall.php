<?php

/**
 * Payment Gateways Module - Uninstallation Script
 *
 * Runs when the module is uninstalled via TadreebLMS External Apps system.
 *
 * This script:
 *   1. Removes the "manage payment gateways" Spatie permission
 *   2. Removes copied helper files from the main app
 */

use Illuminate\Support\Facades\File;
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

// ---------------------------------------------------------------------------
// 2. Remove copied helper files
// ---------------------------------------------------------------------------
try {
    $helpersDir = app_path('Helpers/Payment');

    if (File::isDirectory($helpersDir)) {
        File::deleteDirectory($helpersDir);
        Log::info('[PaymentGateways] Removed app/Helpers/Payment/ directory.');
    }
} catch (\Exception $e) {
    Log::error('[PaymentGateways] Failed to remove helper files: ' . $e->getMessage());
}

// ---------------------------------------------------------------------------
// 3. Remove published public assets
// ---------------------------------------------------------------------------
try {
    $publicDir = public_path('modules/payments');

    if (File::isDirectory($publicDir)) {
        File::deleteDirectory($publicDir);
        Log::info('[PaymentGateways] Removed public/modules/payments/ directory.');
    }
} catch (\Exception $e) {
    Log::error('[PaymentGateways] Failed to remove public assets: ' . $e->getMessage());
}

// Uninstall complete – no echo to avoid corrupting JSON response
