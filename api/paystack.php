<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../config/paystack_config.php';

$input = api_input();
$amount = (int)($input['amount'] ?? 0);
$email = trim((string)($input['email'] ?? ''));

if ($amount <= 0 || $email === '') {
    api_send(['status' => 'error', 'message' => 'amount and email are required'], 400);
}

if (!function_exists('generatePaystackReference')) {
    require_once __DIR__ . '/../includes/functions.php';
}

$reference = generatePaystackReference();
$payload = [
    'email' => $email,
    'amount' => $amount,
    'reference' => $reference
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.paystack.co/transaction/initialize',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    api_send(['status' => 'error', 'message' => $curlError ?: 'Paystack call failed'], 502);
}

$decoded = json_decode($response, true);
if (!is_array($decoded) || !($decoded['status'] ?? false)) {
    api_send(['status' => 'error', 'message' => $decoded['message'] ?? 'Unable to initialize payment'], 502);
}

api_send([
    'status' => 'success',
    'reference' => $reference,
    'public_key' => PAYSTACK_PUBLIC_KEY,
    'access_code' => $decoded['data']['access_code'] ?? null,
    'authorization_url' => $decoded['data']['authorization_url'] ?? null,
    'http_code' => $httpCode
]);

