<?php

declare(strict_types=1);

require __DIR__ . '/../src/MakePayException.php';
require __DIR__ . '/../src/Webhook.php';
require __DIR__ . '/WebhookTest.php';

\MakePay\Tests\WebhookTest::run();

echo "MakePay PHP SDK tests passed.\n";
