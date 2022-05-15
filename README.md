# PHP Zalo Pay Service

## About

PHP Zalo Pay integration.

## Todo

- [ ] Payment
- [x] Disbursement

## Methods

### ZaloPay :: __construct ( `array` params, `string` env )

Create Zalo Pay service instance

- `param` must contain `appID`, `paymentID`, `hMacKey`, `privateKey`
- `env`: `ZaloPay::ENV_PRODUCTION = 1` | `ZaloPay::ENV_SANDBOX` | `ZaloPay::ENV_SANDBOX_QC`

```php
$params = [
    'appID'      => 'appID',
    'paymentID'  => 'paymentID',
    'hMacKey'    => 'hMacKey',
    'privateKey' => 'privateKey',
];

$zaloPay = new ZaloPay($params, ZaloPay::ENV_SANDBOX_QC);
```

### ZaloPayDisbursement :: queryUser ( `string` phone, `string` &requestID ) : `array`

Query Zalo Pay user info.

### ZaloPayDisbursement :: transferFund ( ... ) : `array`

Transfer fund to user's wallet.

Params:

- `string` mUID : response in `queryUser` function
- `int`    amount
- `string` description
- `array`  partnerEmbedData
- `array`  extraInfo
- `string` &partnerOrderID

### ZaloPayDisbursement :: queryOrder ( `string` partnerOrderID, `string` &requestID ) : `array`

Query order info (status,...).

### ZaloPayDisbursement :: queryBalance ( `string` &requestID ) : `array`

Query merchant's wallet balance.

### ZaloPayDisbursement :: disbursement ( ... , `int` maxQueryTimes) : `bool`

Transfer fund to user's wallet & query status.

```php
// Query user
$phone = '0987654321';
$queryUserRequestID = '';
$userInfoResponse = $zaloPay->queryUser($phone, $queryUserRequestID);

if ($userInfoResponse['return_code'] != ZaloPay::RETURN_CODE_SUCCESS) {
    return false;
}

// Disbursement
$partnerOrderID = '';
return $zaloPay->disbursement($userInfoResponse['data']['m_u_id'], 1, 'Demo disbursement', [], [], $partnerOrderID, 3);
```
