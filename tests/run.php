<?php

declare(strict_types=1);

require __DIR__ . '/../src/MakePayException.php';
require __DIR__ . '/../src/Webhook.php';
require __DIR__ . '/../src/Client.php';
require __DIR__ . '/HttpTest.php';
require __DIR__ . '/WebhookTest.php';

\MakePay\Tests\WebhookTest::run();
\MakePay\Tests\HttpTest::run();

$client = new \MakePay\Client(array('keyId' => 'mk_test', 'keySecret' => 'mksec_test'));

assert(\MakePay\Client::VERSION === '0.3.0');
assert($client->hostedCheckoutUrl('pay_123') === 'https://makepay.io/payment/pay_123');
assert($client->hostedDonationUrl('spring-campaign') === 'https://makepay.io/donations/spring-campaign');
assert(
    $client->embeddedCheckoutUrl('pay_123', array('parentOrigin' => 'https://merchant.example')) ===
    'https://makepay.io/embed/payment/pay_123?parentOrigin=https%3A%2F%2Fmerchant.example'
);
assert(
    $client->embeddedDonationUrl('spring-campaign', array('parentOrigin' => 'https://merchant.example')) ===
    'https://makepay.io/embed/donations/spring-campaign?parentOrigin=https%3A%2F%2Fmerchant.example'
);
assert($client->modalScriptUrl() === 'https://makepay.io/modal/makepay.js');
assert(strpos($client->embedButtonHtml('pay_"<&'), 'data-makepay-payment-link="pay_&quot;&lt;&amp;"') !== false);
assert(strpos($client->iframeHtml('pay_123'), 'src="https://makepay.io/embed/payment/pay_123"') !== false);

$client->setCheckoutBaseUrl('https://checkout.example/');
assert($client->hostedCheckoutUrl('pay_123') === 'https://checkout.example/payment/pay_123');

echo "MakePay PHP SDK tests passed.\n";
