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

use Payum\Core\GatewayInterface;
use Payum\Core\Tests\GenericActionTest;
use Antqa\Payum\Perfectmoney\Api;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
abstract class BaseActionTest extends GenericActionTest
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->getMock(Api::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->getMock(GatewayInterface::class);
    }
}
