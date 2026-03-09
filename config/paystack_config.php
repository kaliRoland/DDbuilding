<?php
// config/paystack_config.php

require_once __DIR__ . '/env.php';

// IMPORTANT: Store keys in .env or server environment variables.
define('PAYSTACK_PUBLIC_KEY', getenv('PAYSTACK_PUBLIC_KEY') ?: '');
define('PAYSTACK_SECRET_KEY', getenv('PAYSTACK_SECRET_KEY') ?: '');

if (!PAYSTACK_PUBLIC_KEY || !PAYSTACK_SECRET_KEY) {
    error_log('Paystack keys are missing. Set PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY in .env or server env.');
}
