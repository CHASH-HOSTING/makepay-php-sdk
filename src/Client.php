<?php

declare(strict_types=1);

namespace MakePay;

final class Client
{
    public const DEFAULT_BASE_URL = 'https://www.makecrypto.io';
    public const VERSION = '0.1.0';

    private string $baseUrl;
    private string $keyId;
    private string $keySecret;
    private bool $debug;
    private ?string $logFile;

    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim((string)($config['baseUrl'] ?? $config['base_url'] ?? self::DEFAULT_BASE_URL), '/');
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
        $body = $email ? ['email' => $email] : [];

        return $this->request(
            'POST',
            '/api/partner/v1/makepay/payment-links/' . rawurlencode($uid) . '/send-request-email',
            $body
        );
    }

    public function getSettings(): array
    {
        return $this->request('GET', '/api/partner/v1/makepay/settings');
    }

    public function updateSettings(array $settings): array
    {
        return $this->request('PUT', '/api/partner/v1/makepay/settings', $settings);
    }

    public function verifyWebhook(string $rawBody, ?string $signatureHeader, string $secret, int $toleranceSeconds = 300): bool
    {
        return Webhook::verify($rawBody, $signatureHeader, $secret, $toleranceSeconds);
    }

    public function parseWebhook(string $rawBody, ?string $signatureHeader, string $secret, int $toleranceSeconds = 300): array
    {
        return Webhook::parse($rawBody, $signatureHeader, $secret, $toleranceSeconds);
    }

    public function request(string $method, string $path, ?array $body = null, array $query = []): array
    {
        $this->assertConfigured();

        $url = $this->baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&');
        }

        $headers = [
            'Accept: application/json',
            'User-Agent: MakePayPHP/' . self::VERSION,
            'X-MakeCrypto-Key-Id: ' . $this->keyId,
            'X-MakeCrypto-Key-Secret: ' . $this->keySecret,
        ];

        $payload = null;
        if ($body !== null && strtoupper($method) !== 'GET') {
            $payload = json_encode($body);
            if ($payload === false) {
                throw new MakePayException('Unable to encode MakePay request body as JSON.', 400);
            }
            $headers[] = 'Content-Type: application/json';
        }

        $this->log('MakePay request ' . strtoupper($method) . ' ' . $url);
        $response = $this->httpRequest(strtoupper($method), $url, $headers, $payload);
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

    private function httpRequest(string $method, string $url, array $headers, ?string $payload): array
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
            curl_close($ch);

            if ($body === false) {
                throw new MakePayException($error ?: 'MakePay API request failed.');
            }

            return ['status' => $status, 'body' => (string)$body];
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $payload ?? '',
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

    private function assertConfigured(): void
    {
        $this->assertNonEmpty($this->baseUrl, 'MakePay base URL is required.');
        $this->assertNonEmpty($this->keyId, 'MakePay API key ID is required.');
        $this->assertNonEmpty($this->keySecret, 'MakePay API key secret is required.');
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
}
