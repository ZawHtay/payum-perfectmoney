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

use Payum\Core\ApiAwareInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Exception\RequestNotSupportedException;
use Antqa\Payum\Perfectmoney\Api;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
class StatusAction implements ActionInterface, ApiAwareInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false === $api instanceof Api) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (null === $model[Api::FIELD_V2_HASH] && null === $model[Api::FIELD_PAYMENT_BATCH_NUM]) {
            $request->markNew();

            return;
        }

        if (array_key_exists(Api::FIELD_PAYMENT_BATCH_NUM, $model) && (int) $model[Api::FIELD_PAYMENT_BATCH_NUM] === 0) {
            $request->markCanceled();

            return;
        }

        if ($model[Api::FIELD_V2_HASH] && $this->api->verifyHash($model[Api::FIELD_V2_HASH], $model->toUnsafeArray())) {
            $request->markCaptured();

            return;
        }

        $request->markFailed();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
