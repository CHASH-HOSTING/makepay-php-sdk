<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MakePay\Client;
use MakePay\MakePayException;

$makepay = new Client([
    'keyId' => getenv('MAKEPAY_KEY_ID'),
    'keySecret' => getenv('MAKEPAY_KEY_SECRET'),
]);

try {
    $response = $makepay->createPaymentLink([
        'title' => 'Order #1042',
        'description' => 'Checkout for order #1042',
        'amount' => '129.99',
        'currency' => 'USDT',
        'orderId' => 'order_1042',
        'customerEmail' => 'buyer@example.com',
        'returnUrl' => 'https://merchant.example/orders/1042',
        'successUrl' => 'https://merchant.example/orders/1042/success',
        'failureUrl' => 'https://merchant.example/orders/1042/pay',
        'expirationTime' => '12h',
    ]);

    header('Location: ' . $response['paymentLink']['publicUrl']);
} catch (MakePayException $error) {
    http_response_code($error->getStatusCode() ?: 500);
    echo $error->getMessage();
}
