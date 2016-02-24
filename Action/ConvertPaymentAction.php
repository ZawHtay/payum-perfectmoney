<?php

/*
 * This file is part of the antqa/payum-perfectmoney package.
 *
 * (c) ant.qa <https://www.ant.qa>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antqa\Payum\Perfectmoney\Action;

use Payum\Core\Request\Convert;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetCurrency;
use Antqa\Payum\Perfectmoney\Api;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
class ConvertPaymentAction extends GatewayAwareAction
{
    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $this->gateway->execute($currency = new GetCurrency($payment->getCurrencyCode()));
        $divisor = pow(10, $currency->exp);

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $details[Api::FIELD_PAYMENT_AMOUNT] = $payment->getTotalAmount() / $divisor;
        $details[Api::FIELD_PAYMENT_UNITS] = $payment->getCurrencyCode();
        $details[Api::FIELD_SUGGESTED_MEMO] = $payment->getDescription();
        $details[Api::FIELD_PAYMENT_ID] = $payment->getNumber();

        $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() === 'array'
            ;
    }
}
