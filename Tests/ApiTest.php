<?php

/*
 * This file is part of the antqa/payum-perfectmoney package.
 *
 * (c) ant.qa <https://www.ant.qa>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antqa\Payum\Perfectmoney\Test;

use Payum\Core\HttpClientInterface;
use Antqa\Payum\Perfectmoney\Api;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
class ApiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function couldBeConstructedWithOptionsOnly()
    {
        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => true,
        ]);

        $this->assertAttributeInstanceOf('Payum\Core\HttpClientInterface', 'client', $api);
    }

    /**
     * @test
     */
    public function couldBeConstructedWithOptionsAndHttpClient()
    {
        $client = $this->createHttpClientMock();
        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => true,
        ], $client);
        $this->assertAttributeSame($client, 'client', $api);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The payee_account, alternate_passphrase, display_name fields are required.
     */
    public function throwIfRequiredOptionsNotSetInConstructor()
    {
        new Api([]);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage The boolean sandbox option must be set.
     */
    public function throwIfSandboxOptionsNotBooleanInConstructor()
    {
        new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => 'notABool'
        ]);
    }

    /**
     * @test
     */
    public function shouldFilterNotSupportedOnPrepareOffsitePayment()
    {
        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => true,
        ], $this->createHttpClientMock());

        $post = $api->preparePayment([
            Api::FIELD_PAYMENT_AMOUNT => 100,
            'FOO' => 'fooVal',
            'BAR' => 'barVal',
        ]);

        $this->assertInternalType('array', $post);
        $this->assertArrayNotHasKey('FOO', $post);
        $this->assertArrayNotHasKey('BAR', $post);
    }

    /**
     * @test
     */
    public function shouldKeepSupportedOnPrepareOffsitePayment()
    {
        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => false,
        ], $this->createHttpClientMock());

        $post = $api->preparePayment([
            Api::FIELD_PAYMENT_AMOUNT => 100,
            Api::FIELD_SUGGESTED_MEMO => 'a desc',
        ]);

        $this->assertInternalType('array', $post);
        $this->assertArrayHasKey(Api::FIELD_PAYMENT_AMOUNT, $post);
        $this->assertEquals(100, $post[Api::FIELD_PAYMENT_AMOUNT]);

        $this->assertArrayHasKey(Api::FIELD_PAYMENT_AMOUNT, $post);
        $this->assertEquals('a desc', $post[Api::FIELD_SUGGESTED_MEMO]);
    }

    /**
     * @test
     */
    public function shouldReturnFalseIfHashNotSetToParams()
    {
        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => true,
        ], $this->createHttpClientMock());
        $this->assertFalse($api->verifyHash(null, []));
    }

    /**
     * @test
     */
    public function shouldReturnFalseIfHashesMisMatched()
    {
        $params = [
            Api::FIELD_V2_HASH => '3236C385DF3288D5EA29A7B7B418185E',
            Api::FIELD_PAYEE_ACCOUNT => 'account',
            Api::FIELD_PAYER_ACCOUNT => 'account',
            Api::FIELD_PAYMENT_AMOUNT => 0.01,
            Api::FIELD_PAYMENT_BATCH_NUM => 1,
            Api::FIELD_PAYMENT_ID => 15,
            API::FIELD_PAYMENT_UNITS => 'USD',
            API::FIELD_SUGGESTED_MEMO => 'test payment invalid hash',
            API::FIELD_TIMESTAMPGMT => 1456652247,
        ];

        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => true,
        ], $this->createHttpClientMock());
        $this->assertNotEquals('invalidHash', $api->calculateHash($params));
        $this->assertFalse($api->verifyHash('invalidHash', $params));
    }

    /**
     * @test
     */
    public function shouldReturnTrueIfHashesMatched()
    {
        $params = [
            Api::FIELD_PAYEE_ACCOUNT => 'account',
            Api::FIELD_PAYER_ACCOUNT => 'account',
            Api::FIELD_PAYMENT_AMOUNT => 0.01,
            Api::FIELD_PAYMENT_BATCH_NUM => 1,
            Api::FIELD_PAYMENT_ID => 15,
            API::FIELD_PAYMENT_UNITS => 'USD',
            API::FIELD_SUGGESTED_MEMO => 'test payment invalid hash',
            API::FIELD_TIMESTAMPGMT => 1456652247,
        ];

        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => true,
        ], $this->createHttpClientMock());

        $params[Api::FIELD_V2_HASH] = $api->calculateHash($params);
        $this->assertTrue($api->verifyHash($params[Api::FIELD_V2_HASH], $params));
    }

    /**
     * @test
     */
    public function shouldChangeAmountIfSandbox()
    {
        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => true,
        ], $this->createHttpClientMock());

        $post = $api->preparePayment([
            Api::FIELD_PAYMENT_AMOUNT => 100,
        ]);

        $this->assertArrayHasKey(Api::FIELD_PAYMENT_AMOUNT, $post);
        $this->assertEquals(0.01, $post[Api::FIELD_PAYMENT_AMOUNT]);
    }

    /**
     * @test
     */
    public function shouldNotChangeAmountIfNotSandbox()
    {
        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => false,
        ], $this->createHttpClientMock());

        $post = $api->preparePayment([
            Api::FIELD_PAYMENT_AMOUNT => 100,
        ]);

        $this->assertArrayHasKey(Api::FIELD_PAYMENT_AMOUNT, $post);
        $this->assertEquals(100, $post[Api::FIELD_PAYMENT_AMOUNT]);
    }

    /**
     * @test
     */
    public function shouldEndpointBeString()
    {
        $api = new Api([
            'alternate_passphrase' => 'passphares',
            'payee_account' => 'account',
            'display_name' => 'payment',
            'sandbox' => false,
        ], $this->createHttpClientMock());

        $this->assertInternalType('string', $api->getApiEndpoint());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|HttpClientInterface
     */
    protected function createHttpClientMock()
    {
        return $this->getMock('Payum\Core\HttpClientInterface');
    }
}
