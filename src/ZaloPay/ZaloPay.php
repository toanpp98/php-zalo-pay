<?php

namespace ZaloPay;

use Exception;

class ZaloPay
{
    const ENV_PRODUCTION = 1;
    const ENV_SANDBOX    = 2;
    const ENV_SANDBOX_QC = 3;

    const DOMAINS = [
        self::ENV_PRODUCTION => 'https://openapi.zalopay.vn/',
        self::ENV_SANDBOX    => 'https://sb-openapi.zalopay.vn/',
        self::ENV_SANDBOX_QC => 'https://sbqc-openapi.zalopay.vn',
    ];

    const RETURN_CODE_SUCCESS    = 1;
    const RETURN_CODE_FAIL       = 2;
    const RETURN_CODE_PROCESSING = 3;

    const SUB_RETURN_CODES = [
        -101  => 'User wallet account not exists',
        -1011 => 'User wallet account has been locked',
        -401  => 'Request param illegal',
        -402  => 'Unauthorized',
        -406  => 'This error will occur when Zalo Pay system has been failed in prepare step',
        -500  => 'ZaloPay system error',
    ];

    protected string $appID;
    protected string $paymentID;
    private string   $hMacKey;
    private string   $privateKey;
    private int      $env;

    public function __construct(array $params, int $env)
    {
        $this->appID      = $params['appID'];
        $this->paymentID  = $params['paymentID'];
        $this->hMacKey    = $params['hMacKey'];
        $this->privateKey = $params['privateKey'];
        $this->env        = $env;
    }

    /**
     * @param array $sigData
     * @return string
     * @throws Exception
     */
    private function generateSig(array $sigData): string
    {
        $mac = $this->generateMac($sigData);

        $isSuccess = openssl_sign($mac, $encrypted, $this->privateKey, OPENSSL_ALGO_SHA256);

        if (!$isSuccess) {
            throw new Exception(openssl_error_string());
        }

        return base64_encode($encrypted);
    }

    /**
     * @param array $macData
     * @return string
     * @throws Exception
     */
    private function generateMac(array $macData): string
    {
        $data = implode('|', $macData);

        $mac = hash_hmac('sha256', $data, $this->hMacKey);

        if (!$mac) {
            throw new Exception('Generate mac error');
        }

        return $mac;
    }

    protected function post(
        string $endpoint,
        array  $params,
        array  $secKeys,
        bool   $useSig
    ): array
    {
        $url = static::DOMAINS[$this->env] . $endpoint;

        try {
            $sigData = array_filter($params, fn($key) => in_array($key, $secKeys), ARRAY_FILTER_USE_KEY);

            if ($useSig) {
                $params['sig'] = $this->generateSig($sigData);
            } else {
                $params['mac'] = $this->generateMac($sigData);
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $params,
            ));

            $response = json_decode(curl_exec($curl), true);

            curl_close($curl);
        } catch (Exception $exception) {
            $response                       = [];
            $response['return_code']        = static::RETURN_CODE_FAIL;
            $response['sub_return_code']    = $exception->getCode();
            $response['sub_return_message'] = $exception->getMessage();
            $response['data']               = [];
            $response['return_message']     = $response;
        }

        return $response;
    }

    public static function timestamp(): int
    {
        return round(microtime(true) * 1000);
    }
}
