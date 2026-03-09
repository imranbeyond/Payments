<?php

namespace Modules\PaymentGateways\Services;

class PaymentGatewayRegistry
{
    /**
     * Get all supported gateway definitions.
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            'stripe' => [
                'name' => 'Stripe',
                'slug' => 'stripe',
                'icon' => 'fab fa-stripe',
                'description' => 'Accept credit/debit card payments globally with Stripe.',
                'env_prefix' => 'STRIPE',
                'fields' => [
                    'api_key' => ['label' => 'Publishable Key', 'type' => 'text', 'placeholder' => 'pk_test_...'],
                    'secret_key' => ['label' => 'Secret Key', 'type' => 'password', 'placeholder' => 'sk_test_...'],
                    'webhook_secret' => ['label' => 'Webhook Secret', 'type' => 'password', 'placeholder' => 'whsec_...', 'required' => false],
                ],
                'test_url' => 'https://api.stripe.com/v1/balance',
                'test_auth' => 'bearer', // Uses Bearer token with secret key
            ],
            'paypal' => [
                'name' => 'PayPal',
                'slug' => 'paypal',
                'icon' => 'fab fa-paypal',
                'description' => 'Accept PayPal wallet and card payments worldwide.',
                'env_prefix' => 'PAYPAL',
                'fields' => [
                    'api_key' => ['label' => 'Client ID', 'type' => 'text', 'placeholder' => 'Your PayPal Client ID'],
                    'secret_key' => ['label' => 'Client Secret', 'type' => 'password', 'placeholder' => 'Your PayPal Client Secret'],
                    'webhook_secret' => ['label' => 'Webhook ID', 'type' => 'password', 'placeholder' => 'Your PayPal Webhook ID', 'required' => false],
                ],
                'test_url_sandbox' => 'https://api-m.sandbox.paypal.com/v1/oauth2/token',
                'test_url_live' => 'https://api-m.paypal.com/v1/oauth2/token',
                'test_auth' => 'basic', // Uses Basic auth with client_id:secret
            ],
            'razorpay' => [
                'name' => 'Razorpay',
                'slug' => 'razorpay',
                'icon' => 'fas fa-rupee-sign',
                'description' => 'Accept payments in India via cards, UPI, wallets, and net banking.',
                'env_prefix' => 'RAZORPAY',
                'fields' => [
                    'api_key' => ['label' => 'Key ID', 'type' => 'text', 'placeholder' => 'rzp_test_...'],
                    'secret_key' => ['label' => 'Key Secret', 'type' => 'password', 'placeholder' => 'Your Razorpay Key Secret'],
                    'webhook_secret' => ['label' => 'Webhook Secret', 'type' => 'password', 'placeholder' => 'Your Razorpay Webhook Secret', 'required' => false],
                ],
                'test_url' => 'https://api.razorpay.com/v1/payments?count=1',
                'test_auth' => 'basic', // Uses Basic auth with key_id:key_secret
            ],
            'payu' => [
                'name' => 'PayU',
                'slug' => 'payu',
                'icon' => 'fas fa-credit-card',
                'description' => 'Accept payments in India and emerging markets via PayU.',
                'env_prefix' => 'PAYU',
                'fields' => [
                    'api_key' => ['label' => 'Merchant Key', 'type' => 'text', 'placeholder' => 'Your PayU Merchant Key'],
                    'secret_key' => ['label' => 'Merchant Salt', 'type' => 'password', 'placeholder' => 'Your PayU Merchant Salt'],
                    'webhook_secret' => ['label' => 'Webhook Secret', 'type' => 'password', 'placeholder' => 'Your PayU Webhook Secret', 'required' => false],
                ],
                'test_url_sandbox' => 'https://test.payu.in/merchant/postservice?form=2',
                'test_url_live' => 'https://info.payu.in/merchant/postservice?form=2',
                'test_auth' => 'payu', // Custom PayU auth
            ],
            'telr' => [
                'name' => 'Telr',
                'slug' => 'telr',
                'icon' => 'fas fa-money-bill-wave',
                'description' => 'Accept payments in the Middle East with Telr payment gateway.',
                'env_prefix' => 'TELR',
                'fields' => [
                    'api_key' => ['label' => 'Store ID', 'type' => 'text', 'placeholder' => 'Your Telr Store ID'],
                    'secret_key' => ['label' => 'Authentication Key', 'type' => 'password', 'placeholder' => 'Your Telr Authentication Key'],
                    'webhook_secret' => ['label' => 'Webhook Secret', 'type' => 'password', 'placeholder' => 'Your Telr Webhook Secret', 'required' => false],
                ],
                'test_url' => 'https://secure.telr.com/gateway/order.json',
                'test_auth' => 'telr', // Custom Telr auth
            ],
            'myfatoorah' => [
                'name' => 'MyFatoorah',
                'slug' => 'myfatoorah',
                'icon' => 'fas fa-file-invoice-dollar',
                'description' => 'Accept payments in GCC and Middle East countries with MyFatoorah.',
                'env_prefix' => 'MYFATOORAH',
                'fields' => [
                    'api_key' => ['label' => 'API Key', 'type' => 'text', 'placeholder' => 'Your MyFatoorah API Key'],
                    'secret_key' => ['label' => 'Secret Key', 'type' => 'password', 'placeholder' => 'Your MyFatoorah Secret Key'],
                    'webhook_secret' => ['label' => 'Webhook Secret', 'type' => 'password', 'placeholder' => 'Your MyFatoorah Webhook Secret', 'required' => false],
                ],
                'test_url_sandbox' => 'https://apitest.myfatoorah.com/v2/InitiatePayment',
                'test_url_live' => 'https://api.myfatoorah.com/v2/InitiatePayment',
                'test_auth' => 'bearer', // Uses Bearer token with API key
            ],
            'tap' => [
                'name' => 'Tap Payments',
                'slug' => 'tap',
                'icon' => 'fas fa-hand-holding-usd',
                'description' => 'Accept payments in GCC and Middle East with Tap Payments.',
                'env_prefix' => 'TAP',
                'fields' => [
                    'api_key' => ['label' => 'Publishable Key', 'type' => 'text', 'placeholder' => 'pk_test_...'],
                    'secret_key' => ['label' => 'Secret Key', 'type' => 'password', 'placeholder' => 'sk_test_...'],
                    'webhook_secret' => ['label' => 'Webhook Secret', 'type' => 'password', 'placeholder' => 'Your Tap Webhook Secret', 'required' => false],
                ],
                'test_url' => 'https://api.tap.company/v2/charges/list',
                'test_auth' => 'bearer', // Uses Bearer token with secret key
            ],
        ];
    }

    /**
     * Get a single gateway definition by slug.
     *
     * @param  string  $slug
     * @return array|null
     */
    public static function get(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /**
     * Get all gateway slugs.
     *
     * @return array
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }
}
