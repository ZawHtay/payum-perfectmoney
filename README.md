# Payum Perfect Money
[![Build Status](https://travis-ci.org/antqa/payum-perfectmoney.png?branch=master)](https://travis-ci.org/antqa/payum-perfectmoney)
[![Total Downloads](https://poser.pugx.org/antqa/payum-perfectmoney/downloads)](https://packagist.org/packages/antqa/payum-perfectmoney)
[![Latest Stable Version](https://poser.pugx.org/antqa/payum-perfectmoney/v/stable)](https://packagist.org/packages/antqa/payum-perfectmoney) 

The Payum extension. It provides [Perfect Money](https://perfectmoney.is) payment integration.

## Installation

```bash
$ composer require antqa/payum-perfectmoney
```

## Configuration

```php
<?php

use Payum\Core\PayumBuilder;
use Payum\Core\Payum;

$payum = (new PayumBuilder)
    ->addGatewayFactory('perfectmoney', function(array $config, GatewayFactoryInterface $coreGatewayFactory) {
        return new \Antqa\Payum\Perfectmoney\PerfectMoneyGatewayFactory($config, $coreGatewayFactory)
    })
    ->addGateway('perfectmoney', [
        'factory' => 'perfectmoney',
        'sandbox' => true,
        'alternate_passphrase' => 'place here',
        'payee_account' => 'place here',
        'display_name' => 'place here',
    ])
    ->getPayum()
;
```

## Payment

### Additional parameters

```php
use Payum\Core\Model\PaymentInterface;
use Antqa\Payum\Perfectmoney\Api;

/** @var PaymentInterface $payment */
$payment->setDetails([
    Api::FIELD_SUGGESTED_MEMO => sprintf('Payment - %s', $product),
    Api::FIELD_PAYMENT_URL_METHOD = 'POST',
    Api::FIELD_NOPAYMENT_URL_METHOD = 'POST',
]);
```

## Symfony integration

```yml
#services.yml

app.payum.perfectmoney.factory_builder:
    class: Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder
    arguments:
        - Antqa\Payum\Perfectmoney\PerfectMoneyGatewayFactory
    tags:
        - { name: payum.gateway_factory_builder, factory: perfectmoney }
```

### Configuration

```yml
#config.yml

payum:
    gateways_v2:
        perfectmoney:
            factory: perfectmoney
            payee_account: %perfectmoney_account%
            alternate_passphrase: %perfectmoney_alternate_passphrase%
            sandbox: %payment_sandbox%
            display_name: place_here
```

## License

Payum Perfect Money is released under the [MIT License](LICENSE).
