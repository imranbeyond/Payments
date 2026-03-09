<?php

namespace Modules\PaymentGateways\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Services\ExternalApps\ExternalAppService;
use Modules\PaymentGateways\Services\PaymentGatewayRegistry;
use Modules\PaymentGateways\Services\GatewayConnectionTester;

class PaymentGatewayController extends Controller
{
    /**
     * Show all payment gateways with status and actions.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $app = \App\Models\ExternalApp::where('slug', 'payment-gateways')->first();

        if (!$app || !$app->is_enabled) {
            return response()->json(['error' => 'Module not available'], 403);
        }

        $gateways = PaymentGatewayRegistry::all();

        // Load current status for each gateway
        foreach ($gateways as $slug => &$gateway) {
            $prefix = $gateway['env_prefix'];
            $gateway['enabled'] = filter_var(
                ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_ENABLED') ?: 'false',
                FILTER_VALIDATE_BOOLEAN
            );
            $gateway['mode'] = ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_MODE') ?: 'sandbox';
            $gateway['has_credentials'] = !empty(ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_API_KEY'))
                && !empty(ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_SECRET_KEY'));
        }

        $viewPath = base_path('modules/payment-gateways/views/settings.blade.php');
        return view()->file($viewPath, compact('gateways'));
    }

    /**
     * Show configuration form for a single gateway.
     */
    public function configure(Request $request, string $slug)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $gateway = PaymentGatewayRegistry::get($slug);
        if (!$gateway) {
            abort(404, 'Gateway not found.');
        }

        $prefix = $gateway['env_prefix'];
        $settings = [
            'mode' => ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_MODE') ?: 'sandbox',
            'api_key' => ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_API_KEY') ?: '',
            'secret_key' => ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_SECRET_KEY') ?: '',
            'webhook_secret' => ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_WEBHOOK_SECRET') ?: '',
        ];

        $viewPath = base_path('modules/payment-gateways/views/gateway-config.blade.php');
        return view()->file($viewPath, compact('gateway', 'slug', 'settings'));
    }

    /**
     * Save gateway configuration to module .env file.
     */
    public function store(Request $request, string $slug)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $gateway = PaymentGatewayRegistry::get($slug);
        if (!$gateway) {
            abort(404, 'Gateway not found.');
        }

        $request->validate([
            'mode' => 'required|in:sandbox,live',
            'api_key' => 'required|string|max:500',
            'secret_key' => 'required|string|max:500',
            'webhook_secret' => 'nullable|string|max:500',
        ]);

        $prefix = $gateway['env_prefix'];
        $service = app(ExternalAppService::class);

        $service->setModuleEnv('payment-gateways', [
            $prefix . '_MODE' => $request->input('mode', 'sandbox'),
            $prefix . '_API_KEY' => $request->input('api_key', ''),
            $prefix . '_SECRET_KEY' => $request->input('secret_key', ''),
            $prefix . '_WEBHOOK_SECRET' => $request->input('webhook_secret', ''),
        ]);

        return redirect('/external-apps/payment-gateways/configure/' . $slug)
            ->with('success', $gateway['name'] . ' configuration saved successfully.');
    }

    /**
     * Toggle a gateway enabled/disabled via AJAX.
     */
    public function toggle(Request $request, string $slug)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $gateway = PaymentGatewayRegistry::get($slug);
        if (!$gateway) {
            return response()->json(['message' => 'Gateway not found'], 404);
        }

        $prefix = $gateway['env_prefix'];
        $currentlyEnabled = filter_var(
            ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_ENABLED') ?: 'false',
            FILTER_VALIDATE_BOOLEAN
        );

        // If enabling, check that credentials exist
        if (!$currentlyEnabled) {
            $apiKey = ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_API_KEY');
            $secretKey = ExternalAppService::staticGetModuleEnv('payment-gateways', $prefix . '_SECRET_KEY');
            if (empty($apiKey) || empty($secretKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please configure ' . $gateway['name'] . ' credentials before enabling.',
                ], 400);
            }
        }

        $newState = !$currentlyEnabled;
        $service = app(ExternalAppService::class);
        $service->setModuleEnv('payment-gateways', [
            $prefix . '_ENABLED' => $newState ? 'true' : 'false',
        ]);

        // Update the enabled list
        $this->updateEnabledList($service);

        return response()->json([
            'success' => true,
            'enabled' => $newState,
            'message' => $gateway['name'] . ($newState ? ' enabled' : ' disabled') . ' successfully.',
        ]);
    }

    /**
     * Test gateway connection via AJAX.
     */
    public function testConnection(Request $request, string $slug)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $gateway = PaymentGatewayRegistry::get($slug);
        if (!$gateway) {
            return response()->json(['message' => 'Gateway not found'], 404);
        }

        $credentials = [
            'api_key' => $request->input('api_key', ''),
            'secret_key' => $request->input('secret_key', ''),
            'mode' => $request->input('mode', 'sandbox'),
        ];

        $tester = new GatewayConnectionTester();
        $result = $tester->test($slug, $credentials);

        return response()->json($result);
    }

    /**
     * Rebuild the GATEWAY_ENABLED_LIST from individual gateway statuses.
     */
    protected function updateEnabledList(ExternalAppService $service): void
    {
        $enabledSlugs = [];
        foreach (PaymentGatewayRegistry::all() as $slug => $gateway) {
            $enabled = filter_var(
                ExternalAppService::staticGetModuleEnv('payment-gateways', $gateway['env_prefix'] . '_ENABLED') ?: 'false',
                FILTER_VALIDATE_BOOLEAN
            );
            if ($enabled) {
                $enabledSlugs[] = $slug;
            }
        }

        $service->setModuleEnv('payment-gateways', [
            'GATEWAY_ENABLED_LIST' => implode(',', $enabledSlugs),
        ]);
    }
}
