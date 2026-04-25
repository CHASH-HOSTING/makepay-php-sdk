<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MakePay\MakePayException;
use MakePay\Webhook;

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_MAKEPAY_SIGNATURE'] ?? null;

try {
    $event = Webhook::parse($rawBody, $signature, getenv('MAKEPAY_WEBHOOK_SECRET'));

    if (($event['event']['type'] ?? '') === 'status_changed') {
        // Update your local order by $event['paymentLink']['merchantOrderId'].
    }

    http_response_code(200);
    echo 'ok';
} catch (MakePayException $error) {
    http_response_code($error->getStatusCode() ?: 400);
    echo $error->getMessage();
}
