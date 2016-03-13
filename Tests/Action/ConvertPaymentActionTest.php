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

use Payum\Core\Model\Payment;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Antqa\Payum\Perfectmoney\Api;
use Antqa\Payum\Perfectmoney\Action\ConvertPaymentAction;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
class ConvertPaymentActionTest extends BaseActionTest
{
    protected $actionClass = 'Antqa\Payum\Perfectmoney\Action\ConvertPaymentAction';

    protected $requestClass = 'Payum\Core\Request\Convert';

    public function provideSupportedRequests()
    {
        return array(
            array(new $this->requestClass(new Payment(), 'array')),
            array(new $this->requestClass($this->getMock(PaymentInterface::class), 'array')),
            array(new $this->requestClass(new Payment(), 'array', $this->getMock('Payum\Core\Security\TokenInterface'))),
        );
    }

    public function provideNotSupportedRequests()
    {
        return array(
            array('foo'),
            array(array('foo')),
            array(new \stdClass()),
            array($this->getMockForAbstractClass('Payum\Core\Request\Generic', array(array()))),
            array(new $this->requestClass(new \stdClass(), 'array')),
            array(new $this->requestClass(new Payment(), 'foobar')),
            array(new $this->requestClass($this->getMock(PaymentInterface::class), 'foobar')),
        );
    }

    /**
     * @test
     */
    public function shouldCorrectlyConvertOrderToDetailsAndSetItBack()
    {
        $order = new Payment();
        $order->setCurrencyCode('USD');
        $order->setTotalAmount(123);
        $order->setDescription('the description');
        $order->setNumber('1234');

        $action = new ConvertPaymentAction();
        $action->setGateway($this->createGatewayMock());

        $action->execute($convert = new Convert($order, 'array'));

        $details = $convert->getResult();

        $this->assertNotEmpty($details);

        $this->assertArrayHasKey(Api::FIELD_PAYMENT_AMOUNT, $details);
        $this->assertEquals(123, $details[Api::FIELD_PAYMENT_AMOUNT]);

        $this->assertArrayHasKey(Api::FIELD_PAYMENT_UNITS, $details);
        $this->assertEquals('USD', $details[Api::FIELD_PAYMENT_UNITS]);

        $this->assertArrayHasKey(Api::FIELD_SUGGESTED_MEMO, $details);
        $this->assertEquals('the description', $details[Api::FIELD_SUGGESTED_MEMO]);

        $this->assertArrayHasKey(Api::FIELD_PAYMENT_ID, $details);
        $this->assertEquals('1234', $details[Api::FIELD_PAYMENT_ID]);
    }

    /**
     * @test
     */
    public function shouldNotOverwriteAlreadySetExtraDetails()
    {
        $order = new Payment();
        $order->setCurrencyCode('USD');
        $order->setTotalAmount(123);
        $order->setDescription('the description');
        $order->setNumber('1234');
        $order->setDetails(array(
            'foo' => 'fooVal',
        ));

        $action = new ConvertPaymentAction();
        $action->setGateway($this->createGatewayMock());

        $action->execute($convert = new Convert($order, 'array'));

        $details = $convert->getResult();

        $this->assertNotEmpty($details);

        $this->assertArrayHasKey('foo', $details);
        $this->assertEquals('fooVal', $details['foo']);
    }
}
