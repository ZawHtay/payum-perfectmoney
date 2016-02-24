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

use GuzzleHttp\Psr7\Request;
use Payum\Core\Bridge\Guzzle\HttpClientFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;

/**
 * @author Piotr Antosik <piotr.antosik@ant.qa>
 */
class Api
{
    const FIELD_PAYEE_ACCOUNT = 'PAYEE_ACCOUNT';
    const FIELD_PAYEE_NAME = 'PAYEE_NAME';
    const FIELD_PAYMENT_AMOUNT = 'PAYMENT_AMOUNT';
    const FIELD_PAYMENT_UNITS = 'PAYMENT_UNITS';
    const FIELD_PAYMENT_URL = 'PAYMENT_URL';
    const FIELD_PAYMENT_URL_METHOD = 'PAYMENT_URL_METHOD';
    const FIELD_NOPAYMENT_URL = 'NOPAYMENT_URL';
    const FIELD_NOPAYMENT_URL_METHOD = 'NOPAYMENT_URL_METHOD';
    const FIELD_AVAILABLE_PAYMENT_METHODS = 'AVAILABLE_PAYMENT_METHODS';
    const FIELD_PAYMENT_ID = 'PAYMENT_ID';
    const FIELD_TIMESTAMPGMT = 'TIMESTAMPGMT';
    const FIELD_PAYMENT_BATCH_NUM = 'PAYMENT_BATCH_NUM';
    const FIELD_PAYER_ACCOUNT = 'PAYER_ACCOUNT';
    const FIELD_SUGGESTED_MEMO = 'SUGGESTED_MEMO';
    const FIELD_V2_HASH = 'V2_HASH';
    const FIELD_SUGGESTED_MEMO_NOCHANGE = 'SUGGESTED_MEMO_NOCHANGE';

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $options = [
        'sandbox' => true,
        'alternate_passphrase' => null,
        'payee_account' => null,
        'display_name' => null,
    ];

    /**
     * @param array $options
     * @param HttpClientInterface $client
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client = null)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'payee_account',
            'alternate_passphrase',
            'display_name',
        ]);

        if (!is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        $this->options = $options;
        $this->client = $client ?: HttpClientFactory::create();
    }
    /**
     * @param array $fields
     *
     * @return array
     */
    protected function doRequest(array $fields)
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $request = new Request('POST', $this->getApiEndpoint(), $headers, http_build_query($fields));
        $response = $this->client->send($request);

        if (false === ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        return $response->getBody()->getContents();
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function preparePayment(array $params)
    {
        $supportedParams = [
            self::FIELD_PAYEE_ACCOUNT => null,
            self::FIELD_PAYEE_NAME => null,
            self::FIELD_PAYMENT_AMOUNT => null,
            self::FIELD_PAYMENT_UNITS => null,
            self::FIELD_PAYMENT_URL => null,
            self::FIELD_PAYMENT_URL_METHOD => null,
            self::FIELD_NOPAYMENT_URL => null,
            self::FIELD_NOPAYMENT_URL_METHOD => null,
            self::FIELD_AVAILABLE_PAYMENT_METHODS => null,
            self::FIELD_PAYMENT_ID => null,
            self::FIELD_SUGGESTED_MEMO => null,
            self::FIELD_SUGGESTED_MEMO_NOCHANGE => null,
        ];

        $params = array_filter(array_replace(
            $supportedParams,
            array_intersect_key($params, $supportedParams)
        ));

        $this->addRequiredParams($params);

        return $params;
    }

    /**
     * @param string $hash
     * @param array $params
     *
     * @return bool
     */
    public function verifyHash($hash, array $params)
    {
        if (empty($hash)) {
            return false;
        }

        return $hash === $this->calculateHash($params);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function calculateHash(array $params)
    {
        $compare = sprintf(
            '%s:%s:%s:%s:%s:%s:%s:%s',
            $params[self::FIELD_PAYMENT_ID],
            $params[self::FIELD_PAYEE_ACCOUNT],
            $params[self::FIELD_PAYMENT_AMOUNT],
            $params[self::FIELD_PAYMENT_UNITS],
            $params[self::FIELD_PAYMENT_BATCH_NUM],
            $params[self::FIELD_PAYER_ACCOUNT],
            strtoupper(md5($this->options['alternate_passphrase'])),
            $params[self::FIELD_TIMESTAMPGMT]
        );

        return strtoupper(md5($compare));
    }

    /**
     * @param array $params
     */
    protected function addRequiredParams(array &$params)
    {
        $params[self::FIELD_PAYEE_ACCOUNT] = $this->options['payee_account'];
        $params[self::FIELD_PAYEE_NAME] = $this->options['display_name'];

        if ($this->isSandbox()) {
            $params[self::FIELD_PAYMENT_AMOUNT] = '0.01';
        }
    }

    /**
     * @return string
     */
    public function getApiEndpoint()
    {
        return 'https://perfectmoney.is/api/step1.asp';
    }

    /**
     * @return bool
     */
    public function isSandbox()
    {
        return $this->options['sandbox'];
    }
}
