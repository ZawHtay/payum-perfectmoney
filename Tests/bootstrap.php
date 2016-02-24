<?php

/*
 * This file is part of the antqa/payum-perfectmoney package.
 *
 * (c) ant.qa <https://www.ant.qa>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


if (!$loader = @include __DIR__.'/../vendor/autoload.php') {
    echo <<<EOM
You must set up the project dependencies by running the following commands:
    curl -s http://getcomposer.org/installer | php
    php composer.phar install
EOM;
    exit(1);
}
$rc = new \ReflectionClass('Payum\Core\GatewayInterface');
$coreDir = dirname($rc->getFileName()).'/Tests';
$loader->add('Payum\Core\Tests', $coreDir);
$loader->add('Payum\Stripe\Tests', $coreDir);