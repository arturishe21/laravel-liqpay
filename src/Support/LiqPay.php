<?php

namespace Arturishe21\LiqPay\Support;

use InvalidArgumentException;

class LiqPay
{
    private string $apiUrl = 'https://www.liqpay.ua/api/';
    private string $checkoutUrl = 'https://www.liqpay.ua/api/3/checkout';
    private string $publicKey;
    private string $privateKey;
    private string $serverResponseCode;

    public function __construct(string $publicKey, string $privateKey, ?string $apiUrl = null)
    {
        if (empty($publicKey)) {
            throw new InvalidArgumentException('public_key is empty');
        }

        if (empty($privateKey)) {
            throw new InvalidArgumentException('private_key is empty');
        }

        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;

        if (null !== $apiUrl) {
            $this->apiUrl = $apiUrl;
        }
    }

    public function api(string $path, array $params = array(), int $timeout = 5): mixed
    {
        if (!isset($params['version'])) {
            throw new InvalidArgumentException('version is null');
        }
        $url         = $this->apiUrl . $path;
        $publicKey  = $this->publicKey;
        $privateKey = $this->privateKey;
        $data        = $this->encodeParams(array_merge(compact('publicKey'), $params));
        $signature   = $this->strToSign($privateKey . $data . $privateKey);
        $postfields  = http_build_query(array(
            'data'  => $data,
            'signature' => $signature
        ));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Avoid MITM vulnerability http://phpsecurity.readthedocs.io/en/latest/Input-Validation.html#validation-of-input-sources
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // Check the existence of a common name and also verify that it matches the hostname provided
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,$timeout);   // The number of seconds to wait while trying to connect
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);          // The maximum number of seconds to allow cURL functions to execute
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $serverOoutput = curl_exec($ch);
        $this->serverResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($serverOoutput);
    }

    public function getResponseCode(): ?string
    {
        return $this->serverResponseCode;
    }

    public function cnbForm(array $params): string
    {
        $language = 'ru';
        if (isset($params['language']) && $params['language'] == 'en') {
            $language = 'en';
        }

        $params    = $this->cnbParams($params);
        $data      = $this->encodeParams($params);
        $signature = $this->cnbSignature($params);

        return sprintf('
            <form method="POST" action="%s" accept-charset="utf-8">
                %s
                %s
            </form>
            ',
            $this->checkoutUrl,
            sprintf('<input type="hidden" name="%s" value="%s" />', 'data', $data),
            sprintf('<input type="hidden" name="%s" value="%s" />', 'signature', $signature),
            $language
        );
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string, mixed>
     */
    public function cnbFormRaw(array $params): array
    {
        $params = $this->cnbParams($params);

        return array(
            'url'       => $this->checkoutUrl,
            'data'      => $this->encodeParams($params),
            'signature' => $this->cnbSignature($params)
        );
    }

    /**
     * @param array<string,mixed> $params
     */
    public function cnbSignature(array $params): string
    {
        $json = $this->encodeParams($this->cnbParams($params));

        return $this->strToSign($this->privateKey . $json . $this->privateKey);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string, mixed>
     */
    private function cnbParams(array $params): array
    {
        $params['public_key'] = $this->publicKey;

        if (!isset($params['version'])) {
            throw new InvalidArgumentException('version is null');
        }
        if (!isset($params['amount'])) {
            throw new InvalidArgumentException('amount is null');
        }
        if (!isset($params['currency'])) {
            throw new InvalidArgumentException('currency is null');
        }

        if (!isset($params['description'])) {
            throw new InvalidArgumentException('description is null');
        }

        return $params;
    }

    /**
     * @param array<string,mixed> $params
     */
    private function encodeParams(array $params): string
    {
        return base64_encode(json_encode($params));
    }

    public function decode_params(string $params): array
    {
        return json_decode(base64_decode($params), true);
    }

    private function strToSign(string $str): string
    {
        return base64_encode(sha1($str, 1));
    }
}
