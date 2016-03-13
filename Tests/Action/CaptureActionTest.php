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
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Antqa\Payum\Perfectmoney\Action\CaptureAction;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
class CaptureActionTest extends BaseActionTest
{
    protected $requestClass = Capture::class;

    protected $actionClass = CaptureAction::class;

    /**
     * @test
     */
    public function shouldBeSubClassOfGatewayAwareAction()
    {
        $rc = new \ReflectionClass(CaptureAction::class);
        $this->assertTrue($rc->isSubclassOf(GatewayAwareAction::class));
    }
    /**
     * @test
     */
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass(CaptureAction::class);
        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldAllowSetApi()
    {
        $expectedApi = $this->createApiMock();
        $action = new CaptureAction();
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
        $action = new CaptureAction();
        $action->setApi(new \stdClass());
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Reply\HttpPostRedirect
     */
    public function shouldRedirectToPerfectMoneySiteIfHashPresentInQuery()
    {
        $model = [
            Api::FIELD_PAYMENT_AMOUNT => 1000,
            Api::FIELD_SUGGESTED_MEMO => 'Payment',
            Api::FIELD_PAYMENT_ID => '1234',
        ];

        $postArray = array_replace($model, [
            Api::FIELD_TIMESTAMPGMT => 1456652247,
            Api::FIELD_V2_HASH => 'testhash',
        ]);
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('preparePayment')
            ->with($model)
            ->will($this->returnValue($postArray));

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetHttpRequest'));

        $action = new CaptureAction();
        $action->setApi($apiMock);
        $action->setGateway($gatewayMock);

        $request = new Capture($model);
        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldUpdateModelWhenComeBackFromPerfectMoneySite()
    {
        $model = [
            Api::FIELD_PAYMENT_AMOUNT => 1000,
            Api::FIELD_SUGGESTED_MEMO => 'Payment',
            Api::FIELD_PAYMENT_ID => '1234',
        ];
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->never())
            ->method('preparePayment')
        ;

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetHttpRequest'))
            ->will($this->returnCallback(function (GetHttpRequest $request) {
                $request->request[Api::FIELD_TIMESTAMPGMT] = 1456652247;
                $request->request[Api::FIELD_V2_HASH] = 'testhash';
            }))
        ;
        $action = new CaptureAction();
        $action->setApi($apiMock);
        $action->setGateway($gatewayMock);
        $request = new Capture($model);
        $action->execute($request);
        $actualModel = $request->getModel();

        $this->assertEquals($model[Api::FIELD_PAYMENT_AMOUNT], $actualModel[Api::FIELD_PAYMENT_AMOUNT]);
        $this->assertEquals($model[Api::FIELD_SUGGESTED_MEMO], $actualModel[Api::FIELD_SUGGESTED_MEMO]);
        $this->assertEquals($model[Api::FIELD_PAYMENT_ID], $actualModel[Api::FIELD_PAYMENT_ID]);
        $this->assertEquals(1456652247, $actualModel[Api::FIELD_TIMESTAMPGMT]);
        $this->assertEquals('testhash', $actualModel[Api::FIELD_V2_HASH]);
    }
}
