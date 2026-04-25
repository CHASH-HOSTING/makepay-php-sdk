<?php

declare(strict_types=1);

namespace MakePay\Tests;

use MakePay\Webhook;

final class WebhookTest
{
    public static function run(): void
    {
        $body = '{"event":{"type":"status_changed"}}';
        $secret = 'whsec_test';
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);
        $header = 't=' . $timestamp . ',v1=' . $signature;

        if (!Webhook::verify($body, $header, $secret)) {
            throw new \RuntimeException('Expected webhook signature to verify.');
        }

        if (Webhook::verify($body, $header, 'wrong')) {
            throw new \RuntimeException('Expected wrong webhook secret to fail.');
        }
    }
}
