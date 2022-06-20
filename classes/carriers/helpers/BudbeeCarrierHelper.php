<?php

namespace Modules\Plugin\KlarnaShippingService\Helper;

use Modules\Plugin\KlarnaShippingService\APIClient;

require_once _PS_MODULE_DIR_ . 'klarnashippingservice/classes/APIClient.php';

class BudbeeCarrierHelper
{
    /**
     * getOrderData
     *
     * @param \Order $order
     * @param string $deliveryBoxId
     * @return string[]
     */
    public static function getBudbeeOrderData(\Order $order, string $deliveryBoxId = ''): array
    {
        $customer = new \customer($order->id_customer);
        $deliveryAddress = new \Address($order->id_address_delivery);

        $mobilephone = $deliveryAddress->phone_mobile;
        if (strlen($mobilephone) < 1) {
            $mobilephone = $deliveryAddress->phone;
        }
        $mobilephone = preg_replace("/[\s\-]+/", '', $mobilephone);

        $collectionId = \Configuration::get('BUDBEE_COLLECTION_ID');
        if (empty($collectionId)) {
            throw new \PrestaShopException('Budbee collectionId is not correctly set up in backoffice.');
        }

        $data['cart'] = [
            'cartId' => $order->reference
        ];
        $data['collectionId'] = $collectionId;
        $data['delivery'] = [
            'name' => $deliveryAddress->firstname . ' ' . $deliveryAddress->lastname,
            'telephoneNumber' => $mobilephone,
            'email' => $customer->email,
            'address' => [
                'street' => $deliveryAddress->address1,
                'street2' => $deliveryAddress->address2,
                'postalCode' => $deliveryAddress->postcode,
                'city' => $deliveryAddress->city,
                'country' => 'SE'
            ],
            'outsideDoor' => false,
        ];
        $data['requireSignature'] = false;
        $data['additionalServices'] = [
            'identificationCheckRequired' => true,
            'recipientMinimumAge' => 18,
        ];

        if (!empty($deliveryBoxId)) {
            $data['productCodes'][] = 'DLVBOX';
            $data['additionalServices']['fraudDetection'] = true;
            $data['boxDelivery'] = [
                'selectedBox' => CommonCarrierHelper::getServicePoint($order->id_cart)
            ];
        }

        return $data;
    }

    /**
     * bookBudbeeParcel
     *
     * @param string $id
     * @param \Order $order
     * @return string[]
     */
    public static function bookBudbeeParcel(string $id, \Order $order): array
    {
        if (!$id) {
            throw new \PrestaShopException('$id cannot be empty.');
        }

        $clientId = \Configuration::get('BUDBEE_CLIENT_ID');
        $secretKey = \Configuration::get('BUDBEE_SECRET_KEY');
        if (empty($clientId) || empty($secretKey)) {
            throw new \PrestaShopException('Budbee keys are not set');
        }

        $headers['Accept'] = 'application/vnd.budbee.multiple.orders-v2+json';
        $headers['Content-Type'] = 'application/vnd.budbee.multiple.orders-v2+json';
        $headers['Authorization'] = 'Basic ' . base64_encode($clientId . ':' . $secretKey);
        $url = 'https://api.budbee.com/multiple/orders/' . $id . '/parcels';

        $data[] = [
            'shipmentId' => '',
            'packageId' => '',
            'dimensions' => [
                'weight' => $order->getTotalWeight() * 1000 //requires to be in gram instead of kilo-gram
            ]
        ];

        $request = new APIClient(curl_init());
        $request->initRequest($headers, json_encode($data), $url, 'POST');

        $result = $request->sendRequest();

        if ($result['info']['http_code'] !== 200 || empty($result['data'][0]['id'])) {
            $message = $result['data']['message'] ?? 'Unknown error';
            throw new \PrestaShopException('Order with id: ' . $order->id . ' could not be booked with budbee:' . $message, $result['info']['http_code']);
        }
        $packageIds = [];
        foreach ($result['data'] as $parcel) {
            $packageIds[] = $parcel['packageId'];
        }

        return $packageIds;
    }

    /**
     * generateShippingLabel
     *
     * @param string[]|string[][] $orderData
     * @param string $pdfPath
     * @return void
     */
    public static function generateShippingLabel(array $orderData, string $pdfPath)
    {
        if (empty($orderData)) {
            throw new \PrestaShopException('orderData cannot be empty.');
        }

        $request = new APIClient(curl_init());

        if (!is_writable(file_exists($pdfPath) ? $pdfPath : dirname($pdfPath))) {
                throw new \PrestaShopException('Could not open file for shipping label: ' . $pdfPath);
        }
        //TODO: Doesnt support multiple parcel booking atm. Multiple parcel booking will be implemented in the future if needed
        foreach ($orderData['parcelIds'] as $parcelId) {
            $url = "https://api.budbee.com/orders/{$orderData['budbeeOrderId']}/{$parcelId}/label";
            $request->initRequest([], '', $url);
            $result = $request->sendRequest(false);
            if ($result['info']['http_code'] != 200) {
                throw new \PrestaShopException('Could not retrive shipping label from Budbee. ' . $pdfPath);
            }
            if (!$result['data']) {
                throw new \PrestaShopException('Empty content returned when retrieving shipping label.');
            }
            file_put_contents($pdfPath, $result['data']);
        }
    }
}
