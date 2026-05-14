# MakePay PHP SDK

Official Composer SDK for MakePay server-side integrations. Use it to create
crypto payment links, donation pages, anonymous public links, customer portals,
subscriptions, POS terminals, products, Simple Shop storefronts, invoices,
bookkeeping records, branded domains, operational settings, and signed webhook
handlers from PHP backends.

- Package: `makepay/makepay-php`
- Packagist: `https://packagist.org/packages/makepay/makepay-php`
- Source: `https://github.com/makecryptoio/makepay-php-sdk`

## Install

```bash
composer require makepay/makepay-php
```

The SDK supports PHP 7.4 or newer and requires `ext-json`. `ext-curl` is used
when available; otherwise JSON requests fall back to PHP streams.

## Configure

Create a MakePay API key in MakeCrypto and keep the secret on your server only.

```php
use MakePay\Client;

$makepay = new Client([
    'keyId' => getenv('MAKEPAY_KEY_ID'),
    'keySecret' => getenv('MAKEPAY_KEY_SECRET'),
]);
```

The client sends `X-MakeCrypto-Key-Id` and `X-MakeCrypto-Key-Secret` headers to
the MakePay partner API. You can pass `baseUrl` for a non-production MakeCrypto
API origin, and `checkoutBaseUrl` for a custom MakePay checkout origin.

## Payment Links

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

Read, update, and email existing links:

```php
$makepay->listPaymentLinks();
$makepay->getPaymentLink('PAYMENT_LINK_UID');
$makepay->updatePaymentLink('PAYMENT_LINK_UID', ['status' => 'paused']);
$makepay->sendPaymentRequestEmail('PAYMENT_LINK_UID', 'buyer@example.com');
```

## Donations

```php
$donation = $makepay->createDonationLink([
    'title' => 'Spring campaign',
    'description' => 'Support the 2026 spring fundraiser.',
    'defaultAmountUsd' => '25',
    'minimumAmountUsd' => '5',
    'donationSlug' => 'spring-campaign',
]);

$makepay->listDonationLinks();
$makepay->getDonationLink('DONATION_UID');
$makepay->updateDonationLink('DONATION_UID', ['status' => 'paused']);
```

## Anonymous Payment Links

Anonymous links do not use a MakePay API key. They require an explicit
settlement route because MakePay cannot read merchant wallet settings.

```php
$response = Client::createAnonymousPaymentLink([
    'amount' => '25',
    'settlement' => [
        'currency' => 'USDT',
        'priorities' => [
            [
                'chain' => 'ETH',
                'address' => '0xYourSettlementWallet',
                'asset' => 'ETH.USDT-0xdAC17F958D2ee523a2206206994597C13D831ec7',
            ],
        ],
    ],
    'title' => 'Invoice #1042',
    'customerEmail' => 'buyer@example.com',
    'webhookUrl' => 'https://merchant.example/webhooks/makepay',
]);
```

## Checkout URLs And Embeds

```php
$paymentUid = $response['paymentLink']['uid'];

$hostedUrl = $makepay->hostedCheckoutUrl($paymentUid);
$embedUrl = $makepay->embeddedCheckoutUrl($paymentUid, [
    'parentOrigin' => 'https://merchant.example',
]);

echo $makepay->embedButtonHtml($paymentUid, [
    'buttonLabel' => 'Pay with crypto',
]);

echo $makepay->iframeHtml($paymentUid, [
    'iframeTitle' => 'Secure MakePay checkout',
]);
```

Donation pages have URL helpers too:

```php
$makepay->hostedDonationUrl('spring-campaign');
$makepay->embeddedDonationUrl('spring-campaign', [
    'parentOrigin' => 'https://merchant.example',
]);
```

## Customers And Subscriptions

```php
$makepay->upsertCustomer([
    'email' => 'buyer@example.com',
    'name' => 'Buyer Example',
    'clientId' => 'crm_123',
    'metadata' => ['plan' => 'pro'],
]);

$makepay->listCustomers();

$makepay->createCustomerPortal('CUSTOMER_ID', [
    'returnUrl' => 'https://merchant.example/account',
]);

$makepay->createSubscription([
    'amountUsd' => '29',
    'customerEmail' => 'buyer@example.com',
    'label' => 'Monthly plan',
    'billingIntervalUnit' => 'month',
    'billingIntervalCount' => 1,
    'sendPaymentRequestEmail' => true,
]);

$makepay->listSubscriptions();
```

## POS, Products, And Simple Shop

```php
$terminal = $makepay->createPosTerminal([
    'name' => 'Front counter',
    'pin' => '1234',
    'allowedAssets' => ['ETH.USDT-0xdAC17F958D2ee523a2206206994597C13D831ec7'],
    'emailCollectionMode' => 'optional_after_deposit',
    'catalogEnabled' => true,
]);

$makepay->listPosTerminals();
$makepay->updatePosTerminal('POS_UID', ['name' => 'Front counter', 'pin' => '5678']);
```

```php
$makepay->createProduct([
    'name' => 'Digital guide',
    'productType' => 'digital',
    'basePriceUsd' => '19',
    'shopSlug' => 'digital-guide',
    'images' => [
        ['url' => 'https://merchant.example/guide.png', 'alt' => 'Guide cover'],
    ],
]);

$makepay->createProductDownload('PRODUCT_UID', [
    'fileName' => 'guide.pdf',
    'contentType' => 'application/pdf',
    'url' => 'https://merchant.example/downloads/guide.pdf',
]);

$makepay->updateShop([
    'slug' => 'merchant-shop',
    'displayCurrency' => 'USD',
    'checkoutMode' => 'hosted',
    'branding' => ['accentColor' => '#14b8a6'],
]);

$makepay->updateShopDomain('shop.merchant.example');
$makepay->refreshShopDomain();
$makepay->createShopCoupon([
    'code' => 'SPRING10',
    'discountType' => 'percent',
    'value' => '10',
]);
$makepay->listShopOrders(['status' => 'paid', 'limit' => 25]);
```

## Invoices And Bookkeeping

```php
$makepay->createBookkeepingInvoice([
    'title' => 'Invoice #1042',
    'currency' => 'USD',
    'issueDate' => '2026-05-15',
    'dueDate' => '2026-05-30',
    'counterparty' => [
        'name' => 'Buyer Example',
        'email' => 'buyer@example.com',
        'clientId' => 'crm_123',
    ],
    'lineItems' => [
        [
            'description' => 'Implementation services',
            'quantity' => '1',
            'unitAmount' => '500',
            'taxAmount' => '0',
        ],
    ],
]);

$makepay->createBookkeepingInvoicePaymentLink('INVOICE_UID', [
    'sendPaymentRequestEmail' => true,
]);

$makepay->listBookkeepingInvoices();
$makepay->getBookkeepingInvoice('INVOICE_UID');
$makepay->updateBookkeepingInvoice('INVOICE_UID', ['status' => 'open']);
```

Expenses can be created manually or from wallet activity:

```php
$makepay->createBookkeepingExpense([
    'title' => 'Hosting',
    'amount' => '49',
    'currency' => 'USD',
    'incurredOn' => '2026-05-15',
    'category' => 'Infrastructure',
    'counterparty' => ['name' => 'Vendor Example', 'type' => 'vendor'],
]);

$makepay->createBookkeepingExpenseFromActivity([
    'walletActivityEventKey' => 'CHAIN_EVENT_KEY',
    'category' => 'Settlement',
]);

$makepay->createBookkeepingReconciliation([
    'invoiceId' => 'INVOICE_UID',
    'paymentSessionId' => 'PAYMENT_SESSION_ID',
    'linkType' => 'payment',
]);
```

Document uploads accept a local path string or `CURLFile`:

```php
$makepay->uploadBookkeepingDocument([
    'file' => __DIR__ . '/receipt.pdf',
    'fileName' => 'receipt.pdf',
    'contentType' => 'application/pdf',
    'documentType' => 'receipt',
    'expenseId' => 'EXPENSE_UID',
]);

$makepay->listBookkeepingDocuments();
$makepay->getBookkeepingDocumentDownloadUrl('DOCUMENT_UID');
$makepay->runBookkeepingDocumentOcr('DOCUMENT_UID');
$makepay->getBookkeepingSummary();
```

## Branding And Operational APIs

```php
$makepay->updateBranding([
    'brandName' => 'Merchant',
    'supportEmail' => 'support@merchant.example',
    'brandingBrandColor' => '#111827',
    'brandingAccentColor' => '#14b8a6',
    'paymentLinkTheme' => 'system',
    'paymentLinkDomain' => 'pay.merchant.example',
    'emailSendingDomain' => 'mail.merchant.example',
]);

$makepay->getBranding();
$makepay->refreshBrandingDomains('all');

$makepay->getSettings();
$makepay->updateSettings([
    'callbackUrl' => 'https://merchant.example/webhooks/makepay',
]);
$makepay->listDestinationAssets();
$makepay->listWebhookRequests(['limit' => 25]);
```

## Webhook Verification

Read the exact raw request body before parsing JSON.

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

Use `Webhook::verify()` when you only need a boolean result.

## Method Coverage

| Area | SDK methods |
| --- | --- |
| Payment links | `createPaymentLink`, `listPaymentLinks`, `getPaymentLink`, `updatePaymentLink`, `sendPaymentRequestEmail` |
| Donations | `createDonationLink`, `listDonationLinks`, `getDonationLink`, `updateDonationLink` |
| Anonymous links | `Client::createAnonymousPaymentLink` |
| Checkout | hosted, embedded, modal, button, iframe, and donation URL helpers |
| Customers | `listCustomers`, `upsertCustomer`, `createCustomerPortal` |
| Subscriptions | `listSubscriptions`, `createSubscription` |
| POS terminals | `listPosTerminals`, `createPosTerminal`, `getPosTerminal`, `updatePosTerminal` |
| Products | `listProducts`, `createProduct`, `getProduct`, `updateProduct`, `listProductDownloads`, `createProductDownload` |
| Simple Shop | `getShop`, `updateShop`, `getShopBuilder`, `updateShopBuilder`, `getShopDomain`, `updateShopDomain`, `refreshShopDomain`, coupon and order methods |
| Bookkeeping | summary, invoice, expense, document upload/OCR, and reconciliation methods |
| Branding | `getBranding`, `updateBranding`, `refreshBrandingDomains` |
| Operations | `getSettings`, `updateSettings`, `listDestinationAssets`, `listWebhookRequests` |
| Webhooks | `Webhook::verify`, `Webhook::parse`, plus client proxy methods |

## Data And Errors

Payload arrays use the same camelCase field names as the MakePay API and npm
SDK. Use strings for decimal money values when precision matters, and ISO date
strings for date fields such as `issueDate`.

API errors throw `MakePay\MakePayException` with the HTTP status code and
decoded response body.

```php
use MakePay\MakePayException;

try {
    $makepay->getPaymentLink('PAYMENT_LINK_UID');
} catch (MakePayException $error) {
    error_log($error->getMessage());
    error_log((string) $error->getStatusCode());
}
```
