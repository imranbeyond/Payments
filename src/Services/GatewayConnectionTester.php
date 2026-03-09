<?php

namespace Modules\PaymentGateways\Services;

use Exception;

class GatewayConnectionTester
{
    /**
     * Test a gateway connection by making a lightweight API call.
     *
     * @param  string  $slug      Gateway slug (stripe, paypal, etc.)
     * @param  array   $credentials  Keys: api_key, secret_key, mode
     * @return array{success: bool, message: string}
     */
    public function test(string $slug, array $credentials): array
    {
        if (empty($credentials['api_key']) || empty($credentials['secret_key'])) {
            return [
                'success' => false,
                'message' => 'Please fill in API Key and Secret Key before testing.',
            ];
        }

        $gateway = PaymentGatewayRegistry::get($slug);
        if (!$gateway) {
            return [
                'success' => false,
                'message' => 'Unknown gateway: ' . $slug,
            ];
        }

        try {
            $method = 'test' . str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
            if (method_exists($this, $method)) {
                return $this->$method($credentials);
            }

            return [
                'success' => false,
                'message' => 'Connection test not implemented for this gateway.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Stripe connection - GET /v1/balance with Bearer token.
     */
    protected function testStripe(array $credentials): array
    {
        $ch = curl_init('https://api.stripe.com/v1/balance');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $credentials['secret_key'],
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }

        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Stripe connection successful! API keys are valid.'];
        }

        $data = json_decode($response, true);
        $msg = $data['error']['message'] ?? 'Invalid API keys (HTTP ' . $httpCode . ')';
        return ['success' => false, 'message' => 'Stripe: ' . $msg];
    }

    /**
     * Test PayPal connection - OAuth2 token request with Basic auth.
     */
    protected function testPaypal(array $credentials): array
    {
        $mode = $credentials['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $credentials['api_key'] . ':' . $credentials['secret_key'],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }

        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'PayPal connection successful! Client credentials are valid.'];
        }

        $data = json_decode($response, true);
        $msg = $data['error_description'] ?? 'Invalid credentials (HTTP ' . $httpCode . ')';
        return ['success' => false, 'message' => 'PayPal: ' . $msg];
    }

    /**
     * Test Razorpay connection - GET /v1/payments with Basic auth.
     */
    protected function testRazorpay(array $credentials): array
    {
        $ch = curl_init('https://api.razorpay.com/v1/payments?count=1');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $credentials['api_key'] . ':' . $credentials['secret_key'],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }

        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Razorpay connection successful! API keys are valid.'];
        }

        $data = json_decode($response, true);
        $msg = $data['error']['description'] ?? 'Invalid API keys (HTTP ' . $httpCode . ')';
        return ['success' => false, 'message' => 'Razorpay: ' . $msg];
    }

    /**
     * Test PayU connection - Verify merchant key via API.
     */
    protected function testPayu(array $credentials): array
    {
        $mode = $credentials['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live'
            ? 'https://info.payu.in/merchant/postservice?form=2'
            : 'https://test.payu.in/merchant/postservice?form=2';

        $command = 'verify_payment';
        $hash = hash('sha512', $credentials['api_key'] . '|' . $command . '|test|' . $credentials['secret_key']);

        $ch = curl_init($baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'key' => $credentials['api_key'],
                'command' => $command,
                'var1' => 'test',
                'hash' => $hash,
            ]),
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 0) {
                return ['success' => true, 'message' => 'PayU connection successful! Merchant credentials are valid.'];
            }
            if (isset($data['msg']) && stripos($data['msg'], 'invalid') !== false) {
                return ['success' => false, 'message' => 'PayU: Invalid merchant credentials.'];
            }
            return ['success' => true, 'message' => 'PayU connection successful! Merchant credentials are valid.'];
        }

        return ['success' => false, 'message' => 'PayU: Connection failed (HTTP ' . $httpCode . ')'];
    }

    /**
     * Test Telr connection - Create a minimal check request.
     */
    protected function testTelr(array $credentials): array
    {
        $ch = curl_init('https://secure.telr.com/gateway/order.json');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'method' => 'check',
                'store' => $credentials['api_key'],
                'authkey' => $credentials['secret_key'],
                'order' => ['ref' => 'connection-test'],
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }

        $data = json_decode($response, true);

        // Telr returns error with specific codes; auth errors use code 02
        if (isset($data['error'])) {
            $errorMsg = $data['error']['message'] ?? 'Unknown error';
            $errorCode = $data['error']['code'] ?? '';
            if (in_array($errorCode, ['02', '03'])) {
                return ['success' => false, 'message' => 'Telr: Invalid Store ID or Authentication Key.'];
            }
            // Other errors (like missing order data) mean auth passed
            return ['success' => true, 'message' => 'Telr connection successful! Store credentials are valid.'];
        }

        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Telr connection successful! Store credentials are valid.'];
        }

        return ['success' => false, 'message' => 'Telr: Connection failed (HTTP ' . $httpCode . ')'];
    }

    /**
     * Test MyFatoorah connection - InitiatePayment with Bearer token.
     */
    protected function testMyfatoorah(array $credentials): array
    {
        $mode = $credentials['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live'
            ? 'https://api.myfatoorah.com'
            : 'https://apitest.myfatoorah.com';

        $ch = curl_init($baseUrl . '/v2/InitiatePayment');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'InvoiceAmount' => 1,
                'CurrencyIso' => 'KWD',
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $credentials['api_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['IsSuccess']) && $data['IsSuccess']) {
            return ['success' => true, 'message' => 'MyFatoorah connection successful! API key is valid.'];
        }

        $msg = $data['Message'] ?? $data['ValidationErrors'][0]['Error'] ?? 'Invalid API key (HTTP ' . $httpCode . ')';
        return ['success' => false, 'message' => 'MyFatoorah: ' . $msg];
    }

    /**
     * Test Tap Payments connection - List charges with Bearer token.
     */
    protected function testTap(array $credentials): array
    {
        $ch = curl_init('https://api.tap.company/v2/charges/list');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'period' => ['date' => ['from' => time() - 86400, 'to' => time()]],
                'limit' => 1,
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $credentials['secret_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }

        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Tap Payments connection successful! API keys are valid.'];
        }

        $data = json_decode($response, true);
        $msg = $data['errors'][0]['description'] ?? 'Invalid API keys (HTTP ' . $httpCode . ')';
        return ['success' => false, 'message' => 'Tap Payments: ' . $msg];
    }
}
