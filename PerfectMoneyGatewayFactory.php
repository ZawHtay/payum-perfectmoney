<?php

/*
 * This file is part of the antqa/payum-perfectmoney package.
 *
 * (c) ant.qa <https://www.ant.qa>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antqa\Payum\Perfectmoney;

use Payum\Core\GatewayFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Antqa\Payum\Perfectmoney\Action\CaptureAction;
use Antqa\Payum\Perfectmoney\Action\ConvertPaymentAction;
use Antqa\Payum\Perfectmoney\Action\StatusAction;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
class PerfectMoneyGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'perfectmoney',
            'payum.factory_title' => 'Perfect Money',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'sandbox' => true,
                'alternate_passphrase' => null,
                'payee_account' => null,
                'display_name' => null,
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['payee_account', 'alternate_passphrase', 'display_name'];
            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client']);
            };
        }
    }
}
