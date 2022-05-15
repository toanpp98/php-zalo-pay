<?php

namespace ZaloPay;

use Exception;

class ZaloPayDisbursement extends ZaloPay
{
    const PREFIX             = '/v2/disbursement/';
    const PATH_GET_USER      = self::PREFIX . 'user';
    const PATH_TRANSFER_FUND = self::PREFIX . 'topup';
    const PATH_QUERY_ORDER   = self::PREFIX . 'txn';
    const PATH_QUERY_BALANCE = self::PREFIX . 'balance';

    public function queryUser(string $phone, string &$requestID): array
    {
        $requestID = $requestID ?: uniqid(date('ymd_'));

        $params = [
            'request_id' => $requestID,
            'app_id'     => $this->appID,
            'phone'      => $phone,
            'time'       => static::timestamp(),
        ];

        $secKeys = ['app_id', 'phone', 'time'];

        return $this->post(static::PATH_GET_USER, $params, $secKeys, false);
    }

    public function transferFund(
        string $mUID,
        int    $amount,
        string $description,
        array  $partnerEmbedData,
        array  $extraInfo,
        string &$partnerOrderID
    ): array
    {
        $partnerOrderID = $partnerOrderID ?: uniqid(date('ymd_'));

        $params = [
            'app_id'             => $this->appID,
            'payment_id'         => $this->paymentID,
            'partner_order_id'   => $partnerOrderID,
            'm_u_id'             => $mUID,
            'amount'             => $amount,
            'description'        => $description,
            'partner_embed_data' => json_encode((object)$partnerEmbedData),
            'reference_id'       => $partnerOrderID,
            'extra_info'         => json_encode((object)$extraInfo),
            'time'               => static::timestamp(),
        ];

        $secKeys = [
            'app_id',
            'payment_id',
            'partner_order_id',
            'm_u_id',
            'amount',
            'description',
            'partner_embed_data',
            'extra_info',
            'time',
        ];

        return $this->post(static::PATH_TRANSFER_FUND, $params, $secKeys, true);
    }

    public function queryOrder(string $partnerOrderID, string &$requestID): array
    {
        $requestID = $requestID ?: uniqid(date('ymd_'));

        $params  = [
            'app_id'           => $this->appID,
            'partner_order_id' => $partnerOrderID,
            'time'             => static::timestamp(),
        ];
        $secKeys = array_keys($params);

        return $this->post(static::PATH_QUERY_ORDER, $params, $secKeys, false);
    }

    public function queryBalance(string &$requestID): array
    {
        $requestID = $requestID ?: uniqid(date('ymd_'));

        $params = [
            'request_id' => $requestID,
            'app_id'     => $this->appID,
            'payment_id' => $this->paymentID,
            'time'       => static::timestamp(),
        ];

        $secKeys = ['app_id', 'payment_id', 'time'];

        return $this->post(static::PATH_QUERY_BALANCE, $params, $secKeys, false);
    }

    public function disbursement(
        string $mUID,
        int    $amount,
        string $description,
        array  $partnerEmbedData,
        array  $extraInfo,
        string &$partnerOrderID,
        int    $maxQueryTimes
    ): bool
    {
        $response = $this->transferFund($mUID, $amount, $description, $partnerEmbedData, $extraInfo, $partnerOrderID);

        while ($maxQueryTimes > 0) {
            // Success case
            if (
                $response['return_code'] == ZaloPay::RETURN_CODE_SUCCESS
                && $response['data']['status'] == ZaloPay::RETURN_CODE_SUCCESS
            ) {
                return true;
            }

            // Fail case
            if (
                $response['return_code'] == ZaloPay::RETURN_CODE_FAIL
                || $response['data']['status'] == ZaloPay::RETURN_CODE_FAIL
            ) {
                return false;
            }

            // Processing case => query order
            if (
                $response['return_code'] == ZaloPay::RETURN_CODE_PROCESSING
                || $response['data']['status'] == ZaloPay::RETURN_CODE_PROCESSING
            ) {
                sleep(1);

                $queryRequestID = '';

                $response = $this->queryOrder($partnerOrderID, $queryRequestID);

                $maxQueryTimes--;

                continue;
            }

            // Other errors
            return false;
        }

        return false;
    }
}
