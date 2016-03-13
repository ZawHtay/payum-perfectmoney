<?php

/*
 * This file is part of the antqa/payum-perfectmoney package.
 *
 * (c) ant.qa <https://www.ant.qa>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antqa\Payum\Perfectmoney\Test\Action;

use Antqa\Payum\Perfectmoney\Api;
use Payum\Core\Request\GetHumanStatus;
use Antqa\Payum\Perfectmoney\Action\StatusAction;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
class StatusActionTest extends BaseActionTest
{
    protected $requestClass = 'Payum\Core\Request\GetHumanStatus';

    protected $actionClass = 'Antqa\Payum\Perfectmoney\Action\StatusAction';

    /**
     * @test
     */
    public function shouldMarkNewIfDetailsEmpty()
    {
        $action = new StatusAction();

        $model = [];

        $action->execute($status = new GetHumanStatus($model));

        $this->assertTrue($status->isNew());
    }

    /**
     * @test
     */
    public function shouldMarkCanceledIfPaymentBatchNumIsZero()
    {
        $action = new StatusAction();

        $model = [
            Api::FIELD_PAYMENT_BATCH_NUM => 0,
        ];

        $action->execute($status = new GetHumanStatus($model));

        $this->assertTrue($status->isCanceled());
    }

    /**
     * @test
     */
    public function shouldMarkCapturedIfValidHashAndDetails()
    {
        $action = new StatusAction();
        $action->setApi(new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => true,
        ]));

        $model = [
            Api::FIELD_V2_HASH => '3236C385DF3288D5EA29A7B7B418185E',
            Api::FIELD_PAYEE_ACCOUNT => 'account',
            Api::FIELD_PAYER_ACCOUNT => 'account',
            Api::FIELD_PAYMENT_AMOUNT => 0.01,
            Api::FIELD_PAYMENT_BATCH_NUM => 1,
            Api::FIELD_PAYMENT_ID => 15,
            API::FIELD_PAYMENT_UNITS => 'USD',
            API::FIELD_SUGGESTED_MEMO => 'test payment',
            API::FIELD_TIMESTAMPGMT => 1456652247,
        ];

        $action->execute($status = new GetHumanStatus($model));

        $this->assertTrue($status->isCaptured());
    }

    /**
     * @test
     */
    public function shouldMarkFailedIfHashIsInvalid()
    {
        $action = new StatusAction();
        $action->setApi(new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => true,
        ]));

        $model = [
            Api::FIELD_V2_HASH => 'invalid',
            Api::FIELD_PAYEE_ACCOUNT => 'account',
            Api::FIELD_PAYER_ACCOUNT => 'account',
            Api::FIELD_PAYMENT_AMOUNT => 0.01,
            Api::FIELD_PAYMENT_BATCH_NUM => 1,
            Api::FIELD_PAYMENT_ID => 15,
            API::FIELD_PAYMENT_UNITS => 'USD',
            API::FIELD_SUGGESTED_MEMO => 'test payment',
            API::FIELD_TIMESTAMPGMT => 1456652247,
        ];

        $action->execute($status = new GetHumanStatus($model));

        $this->assertTrue($status->isFailed());
    }

    /**
     * @test
     */
    public function shouldAllowSetApi()
    {
        $expectedApi = $this->createApiMock();
        $action = new StatusAction();
        $action->setApi($expectedApi);
        $this->assertAttributeSame($expectedApi, 'api', $action);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\UnsupportedApiException
     */
    public function throwIfUnsupportedApiGiven()
    {
        $action = new StatusAction();
        $action->setApi(new \stdClass());
    }
}
