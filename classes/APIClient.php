<?php

declare(strict_types=1);

namespace Modules\Plugin\KlarnaShippingService;

class APIClient
{
    const ALLOWED_HTTP_METHODS  = ['GET', 'POST']; //Only GET and POST are used atm.

    /** @var bool $isInitialized */
    private $isInitialized = false;

    /** @var resource $curl */
    private $curl;

    /**
     * __construct
     *
     * @param resource $curl
     * @return void
     */
    public function __construct($curl)
    {
        $this->curl = $curl;
    }

    /**
     * initRequest
     *
     * @param array $headers
     * @param string $body
     * @param string $url
     * @param string $method
     * @throws \PrestaShopException
     * @return void
     */
    public function initRequest(array $headers, string $body, string $url, string $method = 'GET')
    {
        if (!in_array($method, self::ALLOWED_HTTP_METHODS)) {
            throw new \PrestaShopException('HTTP method not allowed');
        }

        if (!empty($headers)) {
            $headersArray = [];
            foreach ($headers as $key => $value) {
                $headersArray[] = $key . ': ' . $value;
            }
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headersArray);
        }

        if ($method && $method === 'POST') {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        $this->isInitialized = true;
    }

    /**
     * sendRequest
     *
     * @param bool $json
     * @throws \PrestaShopException
     * @return array[]|string[][]
     */
    public function sendRequest(bool $json = true): array
    {
        if (!$this->isInitialized) {
            throw new \PrestaShopException('CURL has not been initialized');
        }

        $result = curl_exec($this->curl);

        if ($result === false) {
            throw new \PrestaShopException('HTTP Request failed.');
        }

        $response['info'] = curl_getinfo($this->curl);
        $response['data'] = $json ? json_decode($result, true) : $result;

        curl_close($this->curl);

        return $response;
    }
}
