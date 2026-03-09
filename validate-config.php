<?php

/**
 * Payment Gateways Module - Configuration Validation Script
 *
 * Runs when admin saves configuration via the TadreebLMS configure form.
 *
 * Available in scope:
 *   $app           - ExternalApp model instance
 *   $configuration - array of key-value pairs from the POST form data
 *
 * To signal validation failure: throw an \Exception with the error message.
 */

$gatewayPrefixes = ['STRIPE', 'PAYPAL', 'RAZORPAY', 'PAYU', 'TELR', 'MYFATOORAH', 'TAP'];
$validModes = ['sandbox', 'live'];
$errors = [];

foreach ($gatewayPrefixes as $prefix) {
    $enabledKey = $prefix . '_ENABLED';
    $modeKey = $prefix . '_MODE';
    $apiKey = $prefix . '_API_KEY';
    $secretKey = $prefix . '_SECRET_KEY';

    // Skip gateways that aren't present in the configuration
    if (!isset($configuration[$enabledKey])) {
        continue;
    }

    $isEnabled = filter_var($configuration[$enabledKey] ?? 'false', FILTER_VALIDATE_BOOLEAN);

    // Validate mode if present
    if (isset($configuration[$modeKey]) && !in_array($configuration[$modeKey], $validModes)) {
        $errors[] = $prefix . ' mode must be "sandbox" or "live".';
    }

    // If gateway is enabled, require API key and secret key
    if ($isEnabled) {
        if (empty($configuration[$apiKey])) {
            $errors[] = $prefix . ' API Key is required when the gateway is enabled.';
        }
        if (empty($configuration[$secretKey])) {
            $errors[] = $prefix . ' Secret Key is required when the gateway is enabled.';
        }
    }
}

if (!empty($errors)) {
    throw new \Exception(implode("\n", $errors));
}
