<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MakePay\Client;

$makepay = new Client([
    'keyId' => getenv('MAKEPAY_KEY_ID'),
    'keySecret' => getenv('MAKEPAY_KEY_SECRET'),
]);

$uid = $_GET['uid'] ?? '';
$paymentLink = $makepay->getPaymentLink($uid);

header('Content-Type: application/json');
echo json_encode($paymentLink, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
