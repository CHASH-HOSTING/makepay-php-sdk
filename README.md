# MakePay PHP SDK

Official PHP SDK for MakePay server-side payment links, MakePay settings, and
webhook verification.

## Install

Until the package is published to Packagist, install from the public Git
repository:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/CHASH-HOSTING/makepay-php-sdk"
    }
  ],
  "require": {
    "makepay/makepay-php": "^0.1"
  }
}
```

Then run:

```bash
composer update makepay/makepay-php
```

## Configure

Create a MakePay API key in MakeCrypto and keep the secret on your server only.

```php
use MakePay\Client;

$makepay = new Client([
    'keyId' => getenv('MAKEPAY_KEY_ID'),
    'keySecret' => getenv('MAKEPAY_KEY_SECRET'),
]);
```

## Create a hosted checkout link

```php
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
```

## Read and update payment links

```php
$links = $makepay->listPaymentLinks();
$detail = $makepay->getPaymentLink('PAYMENT_LINK_UID');
$makepay->updatePaymentLink('PAYMENT_LINK_UID', ['status' => 'paused']);
$makepay->sendPaymentRequestEmail('PAYMENT_LINK_UID', 'buyer@example.com');
```

## Verify webhooks

Read the raw request body before JSON parsing.

```php
use MakePay\Webhook;

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_MAKEPAY_SIGNATURE'] ?? null;
$event = Webhook::parse($rawBody, $signature, getenv('MAKEPAY_WEBHOOK_SECRET'));

if (($event['event']['type'] ?? '') === 'status_changed') {
    // Update your local order status.
}

http_response_code(200);
echo 'ok';
```

## API Coverage

- Create, list, read, update, and email MakePay payment links
- Read and update MakePay settings
- Verify and parse signed MakePay webhooks
- Throws `MakePay\MakePayException` with HTTP status and response body on API errors
