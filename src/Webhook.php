<?php

declare(strict_types=1);

namespace MakePay;

final class Webhook
{
    public static function verify(string $rawBody, ?string $signatureHeader, string $secret, int $toleranceSeconds = 300): bool
    {
        if ($signatureHeader === null || trim($signatureHeader) === '' || $secret === '') {
            return false;
        }

        $parts = self::parseSignatureHeader($signatureHeader);
        $timestamp = isset($parts['t']) ? (int)$parts['t'] : 0;
        $signature = isset($parts['v1']) ? (string)$parts['v1'] : '';

        if ($timestamp <= 0 || $signature === '' || !ctype_xdigit($signature)) {
            return false;
        }

        if ($toleranceSeconds > 0 && abs(time() - $timestamp) > $toleranceSeconds) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);

        return hash_equals($expected, strtolower($signature));
    }

    public static function parse(string $rawBody, ?string $signatureHeader, string $secret, int $toleranceSeconds = 300): array
    {
        if (!self::verify($rawBody, $signatureHeader, $secret, $toleranceSeconds)) {
            throw new MakePayException('Invalid MakePay webhook signature.', 401);
        }

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            throw new MakePayException('Invalid MakePay webhook JSON body.', 400);
        }

        return $decoded;
    }

    private static function parseSignatureHeader(string $header): array
    {
        $parts = [];
        foreach (explode(',', $header) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        return $parts;
    }
}
