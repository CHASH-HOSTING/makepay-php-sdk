<?php

declare(strict_types=1);

namespace MakePay\Tests;

use MakePay\Client;

final class HttpTest
{
    public static function run(): void
    {
        if (!function_exists('proc_open')) {
            echo "proc_open not available; skipped MakePay PHP SDK HTTP route tests.\n";
            return;
        }

        $server = self::startServer();
        $receiptPath = tempnam(sys_get_temp_dir(), 'makepay-receipt-');
        if ($receiptPath === false) {
            throw new \RuntimeException('Unable to create receipt fixture.');
        }
        file_put_contents($receiptPath, 'receipt');

        try {
            $client = new Client([
                'baseUrl' => $server['baseUrl'],
                'keyId' => 'mk_test',
                'keySecret' => 'mksec_test',
            ]);

            $responses = [];
            $responses[] = $client->createPaymentLink(['amount' => '12.50', 'currency' => 'USDT']);
            $responses[] = $client->createDonationLink([
                'title' => 'Spring campaign',
                'defaultAmountUsd' => '25',
                'donationSlug' => 'spring-campaign',
            ]);
            $responses[] = $client->listDonationLinks();
            $responses[] = $client->getDonationLink('don_123');
            $responses[] = $client->updateDonationLink('don_123', ['status' => 'paused']);
            $responses[] = $client->listCustomers();
            $responses[] = $client->upsertCustomer(['email' => 'buyer@example.com', 'name' => 'Buyer']);
            $responses[] = $client->createCustomerPortal('cus_123', ['returnUrl' => 'https://merchant.example']);
            $responses[] = $client->listSubscriptions();
            $responses[] = $client->createSubscription(['amountUsd' => '20', 'customerEmail' => 'buyer@example.com']);
            $responses[] = $client->listDestinationAssets();
            $responses[] = $client->listWebhookRequests(['limit' => 10]);
            $responses[] = $client->listPosTerminals();
            $responses[] = $client->createPosTerminal(['name' => 'Front counter', 'pin' => '1234']);
            $responses[] = $client->getPosTerminal('pos_123');
            $responses[] = $client->updatePosTerminal('pos_123', ['name' => 'Front counter', 'pin' => '1234']);
            $responses[] = $client->listProducts();
            $responses[] = $client->createProduct(['name' => 'Sticker', 'basePriceUsd' => '10']);
            $responses[] = $client->getProduct('prod_123');
            $responses[] = $client->updateProduct('prod_123', ['name' => 'Sticker', 'status' => 'active']);
            $responses[] = $client->listProductDownloads('prod_123');
            $responses[] = $client->createProductDownload('prod_123', ['fileName' => 'guide.pdf']);
            $responses[] = $client->getShop();
            $responses[] = $client->updateShop(['slug' => 'demo-shop', 'displayCurrency' => 'USD']);
            $responses[] = $client->getShopBuilder();
            $responses[] = $client->updateShopBuilder(['blocks' => []]);
            $responses[] = $client->getShopDomain();
            $responses[] = $client->updateShopDomain('shop.example.com');
            $responses[] = $client->refreshShopDomain();
            $responses[] = $client->listShopCoupons();
            $responses[] = $client->createShopCoupon(['code' => 'SPRING10', 'discountType' => 'percent', 'value' => '10']);
            $responses[] = $client->updateShopCoupon('coupon_123', ['status' => 'archived']);
            $responses[] = $client->archiveShopCoupon('coupon_123');
            $responses[] = $client->listShopOrders(['limit' => 25, 'status' => 'paid']);
            $responses[] = $client->getBranding();
            $responses[] = $client->updateBranding(['brandName' => 'Merchant', 'paymentLinkDomain' => 'pay.example.com']);
            $responses[] = $client->refreshBrandingDomains('payment-link');
            $responses[] = $client->getBookkeepingSummary();
            $responses[] = $client->listBookkeepingInvoices();
            $responses[] = $client->createBookkeepingInvoice([
                'title' => 'Invoice #1042',
                'currency' => 'USD',
                'counterparty' => ['email' => 'buyer@example.com', 'name' => 'Buyer'],
                'lineItems' => [
                    ['description' => 'Implementation', 'quantity' => '1', 'unitAmount' => '50'],
                ],
            ]);
            $responses[] = $client->getBookkeepingInvoice('inv_123');
            $responses[] = $client->updateBookkeepingInvoice('inv_123', ['status' => 'open']);
            $responses[] = $client->createBookkeepingInvoicePaymentLink('inv_123', [
                'sendPaymentRequestEmail' => true,
            ]);
            $responses[] = $client->listBookkeepingExpenses();
            $responses[] = $client->createBookkeepingExpense(['title' => 'Hosting', 'amount' => '49', 'currency' => 'USD']);
            $responses[] = $client->createBookkeepingExpenseFromActivity(['walletActivityEventKey' => 'chain_event_123']);
            $responses[] = $client->getBookkeepingExpense('exp_123');
            $responses[] = $client->updateBookkeepingExpense('exp_123', ['status' => 'approved']);
            $responses[] = $client->listBookkeepingDocuments();
            $responses[] = $client->uploadBookkeepingDocument([
                'file' => $receiptPath,
                'fileName' => 'receipt.pdf',
                'contentType' => 'application/pdf',
                'documentType' => 'receipt',
                'expenseId' => 'exp_123',
            ]);
            $responses[] = $client->getBookkeepingDocumentDownloadUrl('doc_123');
            $responses[] = $client->runBookkeepingDocumentOcr('doc_123');
            $responses[] = $client->createBookkeepingReconciliation([
                'invoiceId' => 'inv_123',
                'paymentSessionId' => 'session_123',
                'linkType' => 'payment',
            ]);

            $anonymous = Client::createAnonymousPaymentLink([
                'amount' => '5',
                'settlement' => [
                    'currency' => 'USDT',
                    'priorities' => [
                        ['chain' => 'ETH', 'address' => '0xabc', 'asset' => 'ETH.USDT-0xabc'],
                    ],
                ],
            ], ['baseUrl' => $server['baseUrl']]);

            $routes = array_map([self::class, 'route'], $responses);
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/donations');
            self::assertIncludes($routes, 'GET /api/partner/v1/makepay/donations');
            self::assertIncludes($routes, 'GET /api/partner/v1/makepay/customers');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/subscriptions');
            self::assertIncludes($routes, 'GET /api/partner/v1/makepay/destination-assets');
            self::assertIncludes($routes, 'GET /api/partner/v1/makepay/webhook-requests?limit=10');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/pos-terminals');
            self::assertIncludes($routes, 'PATCH /api/partner/v1/makepay/pos-terminals/pos_123');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/products');
            self::assertIncludes($routes, 'PATCH /api/partner/v1/makepay/products/prod_123');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/shop/products/prod_123/downloads');
            self::assertIncludes($routes, 'PATCH /api/partner/v1/makepay/shop');
            self::assertIncludes($routes, 'PUT /api/partner/v1/makepay/shop/builder');
            self::assertIncludes($routes, 'PUT /api/partner/v1/makepay/shop/domains');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/shop/domains');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/shop/coupons');
            self::assertIncludes($routes, 'DELETE /api/partner/v1/makepay/shop/coupons/coupon_123');
            self::assertIncludes($routes, 'GET /api/partner/v1/makepay/shop/orders?limit=25&status=paid');
            self::assertIncludes($routes, 'GET /api/partner/v1/makepay/branding');
            self::assertIncludes($routes, 'PATCH /api/partner/v1/makepay/branding');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/branding/domains/refresh');
            self::assertIncludes($routes, 'GET /api/partner/v1/makepay/bookkeeping');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/bookkeeping/invoices');
            self::assertIncludes($routes, 'PATCH /api/partner/v1/makepay/bookkeeping/invoices/inv_123');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/bookkeeping/invoices/inv_123/payment-link');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/bookkeeping/expenses');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/bookkeeping/expenses/from-activity');
            self::assertIncludes($routes, 'PATCH /api/partner/v1/makepay/bookkeeping/expenses/exp_123');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/bookkeeping/documents');
            self::assertIncludes($routes, 'GET /api/partner/v1/makepay/bookkeeping/documents/doc_123/download');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/bookkeeping/documents/doc_123/ocr');
            self::assertIncludes($routes, 'POST /api/partner/v1/makepay/bookkeeping/reconciliation');

            self::assertHeader($responses[0], 'X-MakeCrypto-Key-Id', 'mk_test');
            if (($responses[0]['json']['payload']['amount'] ?? null) !== '12.50') {
                throw new \RuntimeException('Expected createPaymentLink to send payload amount.');
            }
            if (($responses[1]['json']['payload']['type'] ?? null) !== 'donation') {
                throw new \RuntimeException('Expected createDonationLink to mark payload type donation.');
            }
            if (($responses[49]['files']['file']['name'] ?? '') !== 'receipt.pdf') {
                throw new \RuntimeException('Expected uploadBookkeepingDocument to upload receipt.pdf.');
            }
            if (self::headerValue($anonymous, 'X-MakeCrypto-Key-Id') !== null) {
                throw new \RuntimeException('Anonymous payment link requests must not send API key headers.');
            }
        } finally {
            @unlink($receiptPath);
            self::stopServer($server);
        }
    }

    private static function startServer(): array
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($socket === false) {
            throw new \RuntimeException('Unable to reserve local test port: ' . $error);
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        $port = (int)substr(strrchr($name, ':'), 1);
        $logFile = tempnam(sys_get_temp_dir(), 'makepay-server-');
        if ($logFile === false) {
            throw new \RuntimeException('Unable to create server log file.');
        }

        $router = __DIR__ . '/http-server.php';
        $command = escapeshellarg(PHP_BINARY)
            . ' -S 127.0.0.1:' . $port
            . ' ' . escapeshellarg($router);
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['file', $logFile, 'a'],
            2 => ['file', $logFile, 'a'],
        ], $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start local MakePay SDK test server.');
        }

        fclose($pipes[0]);
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
            if ($connection !== false) {
                fclose($connection);
                return [
                    'baseUrl' => 'http://127.0.0.1:' . $port,
                    'logFile' => $logFile,
                    'process' => $process,
                ];
            }
            usleep(100000);
        }

        self::stopServer(['process' => $process, 'logFile' => $logFile]);
        throw new \RuntimeException('Local MakePay SDK test server did not start.');
    }

    private static function stopServer(array $server): void
    {
        if (isset($server['process']) && is_resource($server['process'])) {
            proc_terminate($server['process']);
            proc_close($server['process']);
        }
        if (isset($server['logFile'])) {
            @unlink($server['logFile']);
        }
    }

    private static function route(array $response): string
    {
        return ($response['method'] ?? '') . ' ' . ($response['uri'] ?? '');
    }

    private static function assertIncludes(array $values, string $needle): void
    {
        if (!in_array($needle, $values, true)) {
            throw new \RuntimeException('Expected request route not found: ' . $needle);
        }
    }

    private static function assertHeader(array $response, string $name, string $expected): void
    {
        $actual = self::headerValue($response, $name);
        if ($actual !== $expected) {
            throw new \RuntimeException(sprintf('Expected header %s to be %s, got %s.', $name, $expected, (string)$actual));
        }
    }

    private static function headerValue(array $response, string $name): ?string
    {
        $headers = $response['headers'] ?? [];
        foreach ($headers as $headerName => $value) {
            if (strcasecmp((string)$headerName, $name) === 0) {
                return (string)$value;
            }
        }

        return null;
    }
}
