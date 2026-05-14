<?php

declare(strict_types=1);

$rawBody = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];

header('Content-Type: application/json');
http_response_code($_SERVER['REQUEST_METHOD'] === 'POST' ? 201 : 200);

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
    'headers' => $headers,
    'body' => $rawBody,
    'json' => $rawBody === '' ? null : json_decode($rawBody, true),
    'post' => $_POST,
    'files' => array_map(static function (array $file): array {
        return [
            'name' => $file['name'] ?? '',
            'type' => $file['type'] ?? '',
            'size' => $file['size'] ?? 0,
            'error' => $file['error'] ?? UPLOAD_ERR_NO_FILE,
        ];
    }, $_FILES),
], JSON_UNESCAPED_SLASHES);
