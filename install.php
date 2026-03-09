<?php

/**
 * Payment Gateways Module - Installation Script
 *
 * Runs when the module is installed via TadreebLMS External Apps system.
 *
 * This script:
 *   1. Creates the "manage payment gateways" Spatie permission and assigns to Administrator
 *   2. Copies PaymentHelper.php into the main app
 */

use Illuminate\Support\Facades\File;
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

// ---------------------------------------------------------------------------
// 2. Copy PaymentHelper into the main app
// ---------------------------------------------------------------------------
try {
    $helpersTarget = app_path('Helpers/Payment');

    // Create target directory if it doesn't exist
    if (!File::isDirectory($helpersTarget)) {
        File::makeDirectory($helpersTarget, 0755, true);
    }

    // Write PaymentHelper.php to the main app
    $helperContent = <<<'HELPER'
<?php

namespace App\Helpers\Payment;

use App\Services\ExternalApps\ExternalAppService;

class PaymentHelper
{
    /**
     * Get all enabled gateway slugs.
     *
     * @return array
     */
    public static function getEnabledGateways(): array
    {
        $list = ExternalAppService::staticGetModuleEnv('payment-gateways', 'GATEWAY_ENABLED_LIST') ?: '';
        if (empty($list)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $list)));
    }

    /**
     * Check if a specific gateway is enabled.
     *
     * @param  string  $slug  Gateway slug (e.g. 'stripe', 'paypal')
     * @return bool
     */
    public static function isGatewayEnabled(string $slug): bool
    {
        return in_array($slug, self::getEnabledGateways(), true);
    }

    /**
     * Get configuration for a specific gateway.
     *
     * @param  string  $slug  Gateway slug (e.g. 'stripe', 'paypal')
     * @return array  Keys: enabled, mode, api_key, secret_key, webhook_secret
     */
    public static function getGatewayConfig(string $slug): array
    {
        $prefix = strtoupper($slug);

        return [
            'enabled' => filter_var(
                ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_ENABLED') ?: 'false',
                FILTER_VALIDATE_BOOLEAN
            ),
            'mode' => ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_MODE') ?: 'sandbox',
            'api_key' => ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_API_KEY') ?: '',
            'secret_key' => ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_SECRET_KEY') ?: '',
            'webhook_secret' => ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_WEBHOOK_SECRET') ?: '',
        ];
    }

    /**
     * Get all gateway configurations (enabled gateways only).
     *
     * @return array  Keyed by gateway slug
     */
    public static function getAllEnabledConfigs(): array
    {
        $configs = [];
        foreach (self::getEnabledGateways() as $slug) {
            $configs[$slug] = self::getGatewayConfig($slug);
        }
        return $configs;
    }
}
HELPER;

    File::put($helpersTarget . '/PaymentHelper.php', $helperContent);
    Log::info('[PaymentGateways] Created PaymentHelper.php in app/Helpers/Payment/');
} catch (\Exception $e) {
    Log::error('[PaymentGateways] Failed to copy helper files: ' . $e->getMessage());
}

// ---------------------------------------------------------------------------
// 3. Public assets symlink is now handled by ExternalAppService::ensurePublicSymlink()
//    during module install and enable. No manual copy needed.
// ---------------------------------------------------------------------------

// Install complete – no echo to avoid corrupting JSON response
