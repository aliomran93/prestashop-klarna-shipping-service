<?php

namespace Modules\Plugin\KlarnaShippingService\Carrier;

use Modules\Plugin\KlarnaShippingService\APIClient;
use Modules\Plugin\KlarnaShippingService\Helper\BudbeeCarrierHelper;
use Modules\Plugin\KlarnaShippingService\Helper\CommonCarrierHelper;

require_once _PS_MODULE_DIR_ . 'klarnashippingservice/classes/APIClient.php';
require_once _PS_MODULE_DIR_ . 'klarnashippingservice/classes/carriers/helpers/BudbeeCarrierHelper.php';

class BudbeeCarrier
{
    /**
     * isCarrierAvailable
     *
     * @param string $postalCode
     * @throws \PrestaShopException
     * @return bool
     */
    public static function isCarrierAvailable(string $postalCode): bool
    {
        $clientId = \Configuration::get('BUDBEE_CLIENT_ID');
        $secretKey = \Configuration::get('BUDBEE_SECRET_KEY');
        if (empty($clientId) || empty($secretKey)) {
            throw new \PrestaShopException('Budbee keys are not set.');
        }
        $headers['Accept'] = 'application/vnd.budbee.postalcodes-v2+json';
        $headers['Content-Type'] = 'application/vnd.budbee.postalcodes-v2+json';
        $headers['Authorization'] = 'Basic ' . base64_encode($clientId . ':' . $secretKey);
        $url = 'https://api.budbee.com/postalcodes/validate/' . $postalCode;

        $request = new APIClient(curl_init());
        $request->initRequest($headers, '', $url);
        $result = $request->sendRequest();

        return $result['info']['http_code'] === 200;
    }

    /**
     * bookBudbeeCarrier
     *
     * @param int $idOrder
     * @param string $orderMessage
     * @param int $employeeId
     * @param string $pdfPath
     * @throws \PrestaShopException
     * @return void
     */
    public function bookBudbeeCarrier(int $idOrder, string $orderMessage, int $employeeId, string $pdfPath)
    {
        $clientId = \Configuration::get('BUDBEE_CLIENT_ID');
        $secretKey = \Configuration::get('BUDBEE_SECRET_KEY');
        if (empty($clientId) || empty($secretKey)) {
            throw new \PrestaShopException('Budbee keys are not set');
        }
        $headers['Accept'] = 'application/vnd.budbee.multiple.orders-v2+json';
        $headers['Content-Type'] = 'application/vnd.budbee.multiple.orders-v2+json';
        $headers['Authorization'] = 'Basic ' . base64_encode($clientId . ':' . $secretKey);
        $url = 'https://api.budbee.com/multiple/orders';
        $order = new \Order($idOrder);
        $orderData = BudbeeCarrierHelper::getBudbeeOrderData($order);

        $request = new APIClient(curl_init());
        $request->initRequest($headers, json_encode($orderData), $url, 'POST');
        $result = $request->sendRequest();

        if ($result['info']['http_code'] !== 200 || empty($result['data']['id'])) {
            $message = $result['data']['message'] ?? 'Unknown error';
            throw new \PrestaShopException('Order with id: ' . $idOrder . ' could not be booked with budbee:' . $message, $result['info']['http_code']);
        }

        $parcelData['budbeeOrderId'] = $result['data']['id'];
        $parcelData['parcelIds'] = BudbeeCarrierHelper::bookBudbeeParcel($parcelData['budbeeOrderId'], $order);
        BudbeeCarrierHelper::generateShippingLabel($parcelData, $pdfPath);
        //TODO: Doesnt support multiple parcel booking atm. Multiple parcel booking will be implemented in the future if needed
        foreach ($parcelData['parcelIds'] as $parcelId) {
            CommonCarrierHelper::saveShippingNumber($idOrder, $parcelId, $orderMessage, $employeeId);
        }
    }
}
