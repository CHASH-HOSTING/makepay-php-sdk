<?php

declare(strict_types=1);

namespace MakePay;

final class Client
{
    public const DEFAULT_BASE_URL = 'https://www.makecrypto.io';
    public const DEFAULT_CHECKOUT_BASE_URL = 'https://makepay.io';
    public const VERSION = '0.3.0';

    private string $baseUrl;
    private string $checkoutBaseUrl;
    private string $keyId;
    private string $keySecret;
    private bool $debug;
    private ?string $logFile;

    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim((string)($config['baseUrl'] ?? $config['base_url'] ?? self::DEFAULT_BASE_URL), '/');
        $this->checkoutBaseUrl = rtrim((string)($config['checkoutBaseUrl'] ?? $config['checkout_base_url'] ?? self::DEFAULT_CHECKOUT_BASE_URL), '/');
        $this->keyId = (string)($config['keyId'] ?? $config['key_id'] ?? $config['apiKeyId'] ?? $config['api_key_id'] ?? '');
        $this->keySecret = (string)($config['keySecret'] ?? $config['key_secret'] ?? $config['apiKeySecret'] ?? $config['api_key_secret'] ?? '');
        $this->debug = (bool)($config['debug'] ?? false);
        $this->logFile = isset($config['logFile']) ? (string)$config['logFile'] : null;
    }

    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    public function setCheckoutBaseUrl(string $checkoutBaseUrl): self
    {
        $this->checkoutBaseUrl = rtrim($checkoutBaseUrl, '/');

        return $this;
    }

    public function setApiKey(string $keyId, string $keySecret): self
    {
        $this->keyId = $keyId;
        $this->keySecret = $keySecret;

        return $this;
    }

    public function setDebug(bool $debug, ?string $logFile = null): self
    {
        $this->debug = $debug;
        $this->logFile = $logFile;

        return $this;
    }

    public function createPaymentLink(array $payload, array $options = []): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/payment-links', [
            'status' => $options['status'] ?? 'active',
            'sendPaymentRequestEmail' => (bool)($options['sendPaymentRequestEmail'] ?? false),
            'payload' => $payload,
        ]);
    }

    public function listPaymentLinks(array $query = []): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/payment-links', null, $query);
    }

    public function getPaymentLink(string $uid): array
    {
        $this->assertNonEmpty($uid, 'Payment link UID is required.');

        return $this->request('GET', '/api/partner/v1/makepay/payment-links/' . rawurlencode($uid));
    }

    public function updatePaymentLink(string $uid, array $updates): array
    {
        $this->assertNonEmpty($uid, 'Payment link UID is required.');

        return $this->request('PATCH', '/api/partner/v1/makepay/payment-links/' . rawurlencode($uid), $updates);
    }

    public function sendPaymentRequestEmail(string $uid, ?string $email = null): array
    {
        $this->assertNonEmpty($uid, 'Payment link UID is required.');
        $body = $email ? ['email' => $email] : (object)[];

        return $this->request(
            'POST',
            '/api/partner/v1/makepay/payment-links/' . rawurlencode($uid) . '/send-request-email',
            $body
        );
    }

    public function createDonationLink(array $payload, array $options = []): array
    {
        $payload['type'] = 'donation';

        return $this->request('POST', '/api/partner/v1/makepay/donations', [
            'status' => $options['status'] ?? 'active',
            'sendPaymentRequestEmail' => (bool)($options['sendPaymentRequestEmail'] ?? false),
            'payload' => $payload,
        ]);
    }

    public function listDonationLinks(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/donations');
    }

    public function getDonationLink(string $uid): array
    {
        $this->assertNonEmpty($uid, 'Donation link UID is required.');

        return $this->request('GET', '/api/partner/v1/makepay/donations/' . rawurlencode($uid));
    }

    public function updateDonationLink(string $uid, array $updates): array
    {
        $this->assertNonEmpty($uid, 'Donation link UID is required.');

        return $this->request('PATCH', '/api/partner/v1/makepay/donations/' . rawurlencode($uid), $updates);
    }

    public static function createAnonymousPaymentLink(array $payload, array $config = []): array
    {
        $client = new self(array_merge($config, [
            'keyId' => 'anonymous',
            'keySecret' => 'anonymous',
        ]));

        return $client->requestPublic('POST', '/api/partner/v1/makepay/payment-links', $payload);
    }

    public static function createAnonymousMakePayPaymentLink(array $payload, array $config = []): array
    {
        return self::createAnonymousPaymentLink($payload, $config);
    }

    public function listCustomers(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/customers');
    }

    public function upsertCustomer(array $payload): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/customers', $payload);
    }

    public function createCustomerPortal(string $customerId, array $payload = []): array
    {
        $this->assertNonEmpty($customerId, 'Customer ID is required.');

        return $this->request(
            'POST',
            '/api/partner/v1/makepay/customers/' . rawurlencode($customerId) . '/portal',
            $payload === [] ? (object)[] : $payload
        );
    }

    public function listSubscriptions(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/subscriptions');
    }

    public function createSubscription(array $payload): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/subscriptions', $payload);
    }

    public function listDestinationAssets(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/destination-assets');
    }

    public function listWebhookRequests(array $query = []): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/webhook-requests', null, $query);
    }

    public function listPosTerminals(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/pos-terminals');
    }

    public function createPosTerminal(array $payload): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/pos-terminals', $payload);
    }

    public function getPosTerminal(string $terminalId): array
    {
        $this->assertNonEmpty($terminalId, 'POS terminal ID is required.');

        return $this->request('GET', '/api/partner/v1/makepay/pos-terminals/' . rawurlencode($terminalId));
    }

    public function updatePosTerminal(string $terminalId, array $payload): array
    {
        $this->assertNonEmpty($terminalId, 'POS terminal ID is required.');

        return $this->request('PATCH', '/api/partner/v1/makepay/pos-terminals/' . rawurlencode($terminalId), $payload);
    }

    public function listProducts(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/products');
    }

    public function createProduct(array $payload): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/products', $payload);
    }

    public function getProduct(string $productId): array
    {
        $this->assertNonEmpty($productId, 'Product ID is required.');

        return $this->request('GET', '/api/partner/v1/makepay/products/' . rawurlencode($productId));
    }

    public function updateProduct(string $productId, array $payload): array
    {
        $this->assertNonEmpty($productId, 'Product ID is required.');

        return $this->request('PATCH', '/api/partner/v1/makepay/products/' . rawurlencode($productId), $payload);
    }

    public function listProductDownloads(string $productId): array
    {
        $this->assertNonEmpty($productId, 'Product ID is required.');

        return $this->request('GET', '/api/partner/v1/makepay/shop/products/' . rawurlencode($productId) . '/downloads');
    }

    public function createProductDownload(string $productId, array $payload): array
    {
        $this->assertNonEmpty($productId, 'Product ID is required.');

        return $this->request('POST', '/api/partner/v1/makepay/shop/products/' . rawurlencode($productId) . '/downloads', $payload);
    }

    public function getShop(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/shop');
    }

    public function updateShop(array $payload): array
    {
        return $this->request('PATCH', '/api/partner/v1/makepay/shop', $payload);
    }

    public function getShopBuilder(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/shop/builder');
    }

    public function updateShopBuilder(array $payload): array
    {
        return $this->request('PUT', '/api/partner/v1/makepay/shop/builder', $payload);
    }

    public function getShopDomain(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/shop/domains');
    }

    public function updateShopDomain($input): array
    {
        $body = is_string($input) || $input === null ? ['domain' => $input] : $input;
        if (!is_array($body)) {
            throw new MakePayException('Shop domain input must be a string, null, or array.', 400);
        }

        return $this->request('PUT', '/api/partner/v1/makepay/shop/domains', $body);
    }

    public function refreshShopDomain($input = []): array
    {
        $body = is_string($input) ? ['domain' => $input] : $input;
        if (!is_array($body)) {
            throw new MakePayException('Shop domain refresh input must be a string or array.', 400);
        }

        return $this->request('POST', '/api/partner/v1/makepay/shop/domains', $body === [] ? (object)[] : $body);
    }

    public function listShopCoupons(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/shop/coupons');
    }

    public function createShopCoupon(array $payload): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/shop/coupons', $payload);
    }

    public function updateShopCoupon(string $couponUid, array $payload): array
    {
        $this->assertNonEmpty($couponUid, 'Shop coupon UID is required.');

        return $this->request('PATCH', '/api/partner/v1/makepay/shop/coupons/' . rawurlencode($couponUid), $payload);
    }

    public function archiveShopCoupon(string $couponUid): array
    {
        $this->assertNonEmpty($couponUid, 'Shop coupon UID is required.');

        return $this->request('DELETE', '/api/partner/v1/makepay/shop/coupons/' . rawurlencode($couponUid));
    }

    public function listShopOrders(array $query = []): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/shop/orders', null, $query);
    }

    public function getBranding(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/branding');
    }

    public function updateBranding(array $payload): array
    {
        return $this->request('PATCH', '/api/partner/v1/makepay/branding', $payload);
    }

    public function refreshBrandingDomains(string $kind = 'all'): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/branding/domains/refresh', [
            'kind' => $kind,
        ]);
    }

    public function getBookkeepingSummary(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/bookkeeping');
    }

    public function listBookkeepingInvoices(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/bookkeeping/invoices');
    }

    public function createBookkeepingInvoice(array $payload): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/bookkeeping/invoices', $payload);
    }

    public function getBookkeepingInvoice(string $invoiceId): array
    {
        $this->assertNonEmpty($invoiceId, 'Bookkeeping invoice ID is required.');

        return $this->request('GET', '/api/partner/v1/makepay/bookkeeping/invoices/' . rawurlencode($invoiceId));
    }

    public function updateBookkeepingInvoice(string $invoiceId, array $payload): array
    {
        $this->assertNonEmpty($invoiceId, 'Bookkeeping invoice ID is required.');

        return $this->request('PATCH', '/api/partner/v1/makepay/bookkeeping/invoices/' . rawurlencode($invoiceId), $payload);
    }

    public function createBookkeepingInvoicePaymentLink(string $invoiceId, array $options = []): array
    {
        $this->assertNonEmpty($invoiceId, 'Bookkeeping invoice ID is required.');

        return $this->request(
            'POST',
            '/api/partner/v1/makepay/bookkeeping/invoices/' . rawurlencode($invoiceId) . '/payment-link',
            $options === [] ? (object)[] : $options
        );
    }

    public function listBookkeepingExpenses(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/bookkeeping/expenses');
    }

    public function createBookkeepingExpense(array $payload): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/bookkeeping/expenses', $payload);
    }

    public function createBookkeepingExpenseFromActivity(array $payload): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/bookkeeping/expenses/from-activity', $payload);
    }

    public function getBookkeepingExpense(string $expenseId): array
    {
        $this->assertNonEmpty($expenseId, 'Bookkeeping expense ID is required.');

        return $this->request('GET', '/api/partner/v1/makepay/bookkeeping/expenses/' . rawurlencode($expenseId));
    }

    public function updateBookkeepingExpense(string $expenseId, array $payload): array
    {
        $this->assertNonEmpty($expenseId, 'Bookkeeping expense ID is required.');

        return $this->request('PATCH', '/api/partner/v1/makepay/bookkeeping/expenses/' . rawurlencode($expenseId), $payload);
    }

    public function listBookkeepingDocuments(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/bookkeeping/documents');
    }

    public function uploadBookkeepingDocument(array $input): array
    {
        return $this->requestMultipart(
            'POST',
            '/api/partner/v1/makepay/bookkeeping/documents',
            $this->buildBookkeepingDocumentParts($input)
        );
    }

    public function getBookkeepingDocumentDownloadUrl(string $documentId): array
    {
        $this->assertNonEmpty($documentId, 'Bookkeeping document ID is required.');

        return $this->request('GET', '/api/partner/v1/makepay/bookkeeping/documents/' . rawurlencode($documentId) . '/download');
    }

    public function runBookkeepingDocumentOcr(string $documentId): array
    {
        $this->assertNonEmpty($documentId, 'Bookkeeping document ID is required.');

        return $this->request('POST', '/api/partner/v1/makepay/bookkeeping/documents/' . rawurlencode($documentId) . '/ocr', (object)[]);
    }

    public function createBookkeepingReconciliation(array $payload): array
    {
        return $this->request('POST', '/api/partner/v1/makepay/bookkeeping/reconciliation', $payload);
    }

    public function getSettings(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/settings');
    }

    public function updateSettings(array $settings): array
    {
        return $this->request('PUT', '/api/partner/v1/makepay/settings', $settings);
    }

    public function hostedCheckoutUrl(string $uid): string
    {
        $this->assertNonEmpty($uid, 'Payment link UID is required.');

        return $this->checkoutBaseUrl . '/payment/' . rawurlencode($uid);
    }

    public function hostedDonationUrl(string $donationSlug): string
    {
        $this->assertNonEmpty($donationSlug, 'Donation slug is required.');

        return $this->checkoutBaseUrl . '/donations/' . rawurlencode($donationSlug);
    }

    public function embeddedCheckoutUrl(string $uid, array $options = []): string
    {
        $this->assertNonEmpty($uid, 'Payment link UID is required.');

        return $this->buildEmbeddedUrl('/embed/payment/' . rawurlencode($uid), $options);
    }

    public function embeddedDonationUrl(string $donationSlug, array $options = []): string
    {
        $this->assertNonEmpty($donationSlug, 'Donation slug is required.');

        return $this->buildEmbeddedUrl('/embed/donations/' . rawurlencode($donationSlug), $options);
    }

    public function modalScriptUrl(): string
    {
        return $this->checkoutBaseUrl . '/modal/makepay.js';
    }

    public function embedButtonHtml(string $uid, array $options = []): string
    {
        $this->assertNonEmpty($uid, 'Payment link UID is required.');

        $buttonLabel = (string) ($options['buttonLabel'] ?? $options['button_label'] ?? 'Pay with crypto');

        return implode("\n", array(
            '<script src="' . $this->escapeHtmlAttribute($this->modalScriptUrl()) . '"></script>',
            '<button type="button" data-makepay-payment-link="' . $this->escapeHtmlAttribute($uid) . '">',
            '  ' . htmlspecialchars($buttonLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '</button>',
        ));
    }

    public function iframeHtml(string $uid, array $options = []): string
    {
        $title = (string) ($options['iframeTitle'] ?? $options['iframe_title'] ?? 'MakePay checkout');

        return implode("\n", array(
            '<iframe',
            '  title="' . $this->escapeHtmlAttribute($title) . '"',
            '  src="' . $this->escapeHtmlAttribute($this->embeddedCheckoutUrl($uid, $options)) . '"',
            '  style="width:100%;min-height:720px;border:0;border-radius:12px;"',
            '  allow="clipboard-read; clipboard-write"',
            '></iframe>',
        ));
    }

    public function verifyWebhook(string $rawBody, ?string $signatureHeader, string $secret, int $toleranceSeconds = 300): bool
    {
        return Webhook::verify($rawBody, $signatureHeader, $secret, $toleranceSeconds);
    }

    public function parseWebhook(string $rawBody, ?string $signatureHeader, string $secret, int $toleranceSeconds = 300): array
    {
        return Webhook::parse($rawBody, $signatureHeader, $secret, $toleranceSeconds);
    }

    public function request(string $method, string $path, $body = null, array $query = []): array
    {
        return $this->sendJsonRequest($method, $path, $body, $query, true);
    }

    private function requestPublic(string $method, string $path, $body = null, array $query = []): array
    {
        return $this->sendJsonRequest($method, $path, $body, $query, false);
    }

    private function sendJsonRequest(string $method, string $path, $body, array $query, bool $authenticated): array
    {
        $method = strtoupper($method);
        $url = $this->buildRequestUrl($path, $query);
        $headers = $this->buildHeaders($authenticated);

        $payload = null;
        if ($body !== null && $method !== 'GET') {
            $payload = json_encode($body);
            if ($payload === false) {
                throw new MakePayException('Unable to encode MakePay request body as JSON.', 400);
            }
            $headers[] = 'Content-Type: application/json';
        }

        return $this->sendRequest($method, $url, $headers, $payload);
    }

    private function requestMultipart(string $method, string $path, array $parts, array $query = []): array
    {
        $method = strtoupper($method);
        $url = $this->buildRequestUrl($path, $query);
        $headers = $this->buildHeaders(true);

        if (function_exists('curl_init')) {
            $payload = $this->buildCurlMultipartPayload($parts);
        } else {
            [$payload, $contentType] = $this->buildStreamMultipartPayload($parts);
            $headers[] = 'Content-Type: ' . $contentType;
        }

        return $this->sendRequest($method, $url, $headers, $payload);
    }

    private function sendRequest(string $method, string $url, array $headers, $payload): array
    {
        $this->log('MakePay request ' . $method . ' ' . $url);
        $response = $this->httpRequest($method, $url, $headers, $payload);
        $decoded = $response['body'] === '' ? [] : json_decode($response['body'], true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = isset($decoded['error'])
                ? (string)$decoded['error']
                : sprintf('MakePay API request failed with HTTP %d.', $response['status']);
            throw new MakePayException($message, $response['status'], $decoded);
        }

        return $decoded;
    }

    private function httpRequest(string $method, string $url, array $headers, $payload): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }

            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            if (PHP_VERSION_ID < 80000) {
                curl_close($ch);
            }

            if ($body === false) {
                throw new MakePayException($error ?: 'MakePay API request failed.');
            }

            return ['status' => $status, 'body' => (string)$body];
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => is_string($payload) ? $payload : '',
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);
        $body = file_get_contents($url, false, $context);
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $status = (int)$match[1];
        }

        if ($body === false) {
            throw new MakePayException('MakePay API request failed.');
        }

        return ['status' => $status, 'body' => (string)$body];
    }

    private function buildHeaders(bool $authenticated): array
    {
        $this->assertNonEmpty($this->baseUrl, 'MakePay base URL is required.');

        $headers = [
            'Accept: application/json',
            'User-Agent: MakePayPHP/' . self::VERSION,
        ];

        if ($authenticated) {
            $this->assertNonEmpty($this->keyId, 'MakePay API key ID is required.');
            $this->assertNonEmpty($this->keySecret, 'MakePay API key secret is required.');
            $headers[] = 'X-MakeCrypto-Key-Id: ' . $this->keyId;
            $headers[] = 'X-MakeCrypto-Key-Secret: ' . $this->keySecret;
        }

        return $headers;
    }

    private function buildRequestUrl(string $path, array $query = []): string
    {
        $url = $this->baseUrl . $path;
        $filtered = [];

        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            $filtered[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }

        if ($filtered !== []) {
            $url .= '?' . http_build_query($filtered, '', '&');
        }

        return $url;
    }

    private function buildEmbeddedUrl(string $path, array $options = []): string
    {
        $url = $this->checkoutBaseUrl . $path;
        $parentOrigin = isset($options['parentOrigin'])
            ? (string) $options['parentOrigin']
            : (string) ($options['parent_origin'] ?? '');

        if ($parentOrigin !== '') {
            $url .= '?' . http_build_query(['parentOrigin' => $parentOrigin], '', '&');
        }

        return $url;
    }

    private function buildBookkeepingDocumentParts(array $input): array
    {
        if (!array_key_exists('file', $input)) {
            throw new MakePayException('Bookkeeping document upload requires a file path or CURLFile.', 400);
        }

        $file = $input['file'];
        $fileName = (string)($input['fileName'] ?? $input['file_name'] ?? '');
        $contentType = (string)($input['contentType'] ?? $input['content_type'] ?? 'application/octet-stream');

        if (class_exists('\\CURLFile') && $file instanceof \CURLFile) {
            $filePath = $file->getFilename();
            $postFilename = method_exists($file, 'getPostFilename') ? (string)$file->getPostFilename() : '';
            $curlMimeType = method_exists($file, 'getMimeType') ? (string)$file->getMimeType() : '';
            $fileName = $fileName !== '' ? $fileName : ($postFilename !== '' ? $postFilename : basename($filePath));
            $contentType = $curlMimeType !== '' ? $curlMimeType : $contentType;
        } elseif (is_string($file)) {
            $filePath = $file;
            $fileName = $fileName !== '' ? $fileName : basename($filePath);
        } else {
            throw new MakePayException('Bookkeeping document file must be a local path string or CURLFile.', 400);
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new MakePayException('Bookkeeping document file is not readable.', 400);
        }

        $parts = [
            [
                'name' => 'file',
                'path' => $filePath,
                'fileName' => $fileName,
                'contentType' => $contentType,
            ],
        ];

        foreach ([
            'documentType' => ['documentType', 'document_type'],
            'invoiceId' => ['invoiceId', 'invoice_id'],
            'expenseId' => ['expenseId', 'expense_id'],
        ] as $fieldName => $aliases) {
            foreach ($aliases as $alias) {
                if (isset($input[$alias]) && (string)$input[$alias] !== '') {
                    $parts[] = [
                        'name' => $fieldName,
                        'value' => (string)$input[$alias],
                    ];
                    break;
                }
            }
        }

        return $parts;
    }

    private function buildCurlMultipartPayload(array $parts): array
    {
        if (!class_exists('\\CURLFile')) {
            throw new MakePayException('CURLFile is required for multipart uploads with cURL.', 500);
        }

        $payload = [];
        foreach ($parts as $part) {
            if (isset($part['path'])) {
                $payload[$part['name']] = new \CURLFile(
                    $part['path'],
                    $part['contentType'] ?? 'application/octet-stream',
                    $part['fileName'] ?? basename($part['path'])
                );
                continue;
            }

            $payload[$part['name']] = (string)($part['value'] ?? '');
        }

        return $payload;
    }

    private function buildStreamMultipartPayload(array $parts): array
    {
        $boundary = 'makepay-' . bin2hex(random_bytes(12));
        $body = '';

        foreach ($parts as $part) {
            $body .= '--' . $boundary . "\r\n";
            if (isset($part['path'])) {
                $body .= 'Content-Disposition: form-data; name="' . $this->escapeMultipartName($part['name']) . '"; filename="' . $this->escapeMultipartName($part['fileName'] ?? basename($part['path'])) . '"' . "\r\n";
                $body .= 'Content-Type: ' . ($part['contentType'] ?? 'application/octet-stream') . "\r\n\r\n";
                $body .= file_get_contents($part['path']) . "\r\n";
                continue;
            }

            $body .= 'Content-Disposition: form-data; name="' . $this->escapeMultipartName($part['name']) . '"' . "\r\n\r\n";
            $body .= (string)($part['value'] ?? '') . "\r\n";
        }

        $body .= '--' . $boundary . "--\r\n";

        return [$body, 'multipart/form-data; boundary=' . $boundary];
    }

    private function assertNonEmpty(string $value, string $message): void
    {
        if (trim($value) === '') {
            throw new MakePayException($message, 400);
        }
    }

    private function log(string $line): void
    {
        if (!$this->debug) {
            return;
        }

        $target = $this->logFile ?: './makepay.log';
        file_put_contents($target, '[' . gmdate('c') . '] ' . $line . PHP_EOL, FILE_APPEND);
    }

    private function escapeHtmlAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function escapeMultipartName(string $value): string
    {
        return str_replace(["\r", "\n", '"'], ['', '', '\\"'], $value);
    }
}
