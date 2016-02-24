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

use Payum\Core\Request\Capture;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Exception\RequestNotSupportedException;
use Antqa\Payum\Perfectmoney\Api;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
class CaptureAction extends GatewayAwareAction implements ApiAwareInterface
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
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        if (isset($httpRequest->request[Api::FIELD_V2_HASH])) {
            $model->replace($httpRequest->request);

            //validate hash
            if (false === $this->api->verifyHash($httpRequest->request[Api::FIELD_V2_HASH], $httpRequest->request)) {

                throw new HttpRedirect((string) $request->getToken()->getAfterUrl());
            }

        } else {
            //payment canceled
            if (isset($httpRequest->request[Api::FIELD_PAYMENT_BATCH_NUM]) && (int) $httpRequest->request[Api::FIELD_PAYMENT_BATCH_NUM] === 0) {
                $model->replace($httpRequest->request);

                throw new HttpRedirect((string) $request->getToken()->getAfterUrl());
            }

            if (false === isset($model[Api::FIELD_PAYMENT_URL]) && $request->getToken()) {
                $model[Api::FIELD_PAYMENT_URL] = $request->getToken()->getTargetUrl();
            }

            if (false === isset($model[Api::FIELD_NOPAYMENT_URL]) && $request->getToken()) {
                $model[Api::FIELD_NOPAYMENT_URL] = $request->getToken()->getTargetUrl();
            }

            throw new HttpPostRedirect(
                $this->api->getApiEndpoint(),
                $this->api->preparePayment($model->toUnsafeArray())
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
