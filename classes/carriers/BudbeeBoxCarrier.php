<?php

namespace Modules\Plugin\KlarnaShippingService\Carrier;

use Modules\Plugin\KlarnaShippingService\APIClient;
use Modules\Plugin\KlarnaShippingService\Helper\BudbeeCarrierHelper;
use Modules\Plugin\KlarnaShippingService\Helper\CommonCarrierHelper;

require_once _PS_MODULE_DIR_ . 'klarnashippingservice/classes/APIClient.php';
require_once _PS_MODULE_DIR_ . '/attribute_unity/attribute_unity.php';
require_once _PS_MODULE_DIR_ . 'klarnashippingservice/classes/carriers/helpers/BudbeeCarrierHelper.php';
require_once _PS_MODULE_DIR_ . 'klarnashippingservice/classes/carriers/helpers/CommonCarrierHelper.php';

class BudbeeBoxCarrier
{
    /**
     * getAllServicePoints
     *
     * @param string $postalCode
     * @param float $price
     * @return string[]
     * @throws \PrestaShopException
     */
    public static function getAllServicePoints(string $postalCode, float $price = 0): array
    {
        $budbeeLockers = self::budbeeAddressLookup($postalCode);
        if (empty($budbeeLockers)) {
            return [];
        }
        $servicePoints = [];
        foreach ($budbeeLockers['lockers'] as $budbeeLocker) {
            $servicePoint = [];
            $locationAddress = [];
            $servicePoint['id'] = $budbeeLocker['id'];
            $servicePoint['name'] = $budbeeLocker['name'];
            $servicePoint['price'] = $price * 100;
            $locationAddress['street_address'] = $budbeeLocker['address']['street'];
            $locationAddress['postal_code'] = $budbeeLocker['address']['postalCode'];
            $locationAddress['city'] = $budbeeLocker['city'];
            $servicePoint['address'] = $locationAddress;
            $servicePoints[] = $servicePoint;
        }
        return $servicePoints;
    }

    /**
     * isCarrierAvailable
     *
     * @param int $id_cart
     * @return bool
     * @throws \PrestaShopException
     */
    public static function isCarrierAvailable(int $id_cart): bool
    {
        $cart = new \Cart($id_cart);
        // TODO: get categories from a query
        $stockCategories = [4, 9, 6, 5, 10, 7, 11, 36, 8, 97, 83, 43];
        $attributeUnity = \attribute_unity::attributeUnityList();

        /** @var float $volume in cm³ (ml) */
        $volume = 0;

        /** @var int a can is about 7 cm x 7 cm x 2½ cm  = 122½ cm³ ≃ 125 cm³ */
        $canVolume = 125;

        /** @var int $maxValume in cm³ Limit around 3 x 11-pack */
        $maxVolume = 33 * $canVolume;

        foreach ($cart->getProducts() as $product) {
            // Load id_product_attribute -> id_attribute
            $attributeCombinations = \Product::getAttributesParams(
                $product['id_product'],
                $product['id_product_attribute']
            );

            // Only products with attributes can have auto-volume
            if (empty($attributeCombinations)) {
                $unity = null;
            } elseif (!is_array($attributeCombinations)) {
                // Only products with attributes can have auto-volume
                $unity = null;
            } elseif (empty($attributeUnity[$attributeCombinations[0]['id_attribute']])) {
                // Only attributes with unity defined can have auto-volume
                $unity = null;
            } else {
                $unity = $attributeUnity[$attributeCombinations[0]['id_attribute']];
            }

            // Get product size
            $sizes = [
                $product["width"],
                $product["height"],
                $product["depth"],
            ];

            // sort dimension by size
            sort($sizes);

            // Have volume?
            if ($sizes[0] > 0) {
                // Box Size: [70 mm, 240 mm, 340 mm] = [7 cm, 24 cm, 34 cm]
                if ($sizes[2] > 34) {
                    // If any side is larger then 34 cm, then it's to large
                    return false;
                }
                if ($sizes[1] > 24) {
                    // If two of the sides is larger then 24 cm, then it's to large
                    return false;
                }
                if ($sizes[0] > 7) {
                    // If all sides is larger then 7 cm, then it's to large
                    return false;
                }

                // Calculate volume in cm³
                $volume += $sizes[0] * $sizes[0] * $sizes[0] * $unity;

                // If the total is already to large, abort
                if ($volume > $maxVolume) {
                    return false;
                }
                continue;
            }

            // Auto volume, only in snuff (snus)
            if (!in_array($product['id_category_default'], $stockCategories)) {
                return false;
            }

            if ($unity === null) {
                return false;
            }

            // Add 125 cm³ for each can (dosa)
            $volume += $canVolume * $product['cart_quantity'] * $unity;

            // If the total is already to large, abort
            if ($volume > $maxVolume) {
                return false;
            }
        }

        return $volume <= $maxVolume;
    }

    /**
     * budbeeAddressLookup
     *
     * @param string $postCode
     * @return string[][]
     * @throws \PrestaShopException
     */
    public static function budbeeAddressLookup(string $postCode): array
    {
        $clientId = \Configuration::get('BUDBEE_CLIENT_ID');
        $secretKey = \Configuration::get('BUDBEE_SECRET_KEY');
        if (empty($clientId) || empty($secretKey)) {
            throw new \PrestaShopException('Budbee keys are not set');
        }
        $headers['Accept'] = 'application/vnd.budbee.boxes-v1+json';
        $headers['Content-Type'] = 'application/vnd.budbee.boxes-v1+json';
        $headers['Authorization'] = 'Basic ' . base64_encode($clientId . ':' . $secretKey);
        $url = 'https://api.budbee.com/boxes/postalcodes/validate/SE/' . $postCode;

        $request = new APIClient(curl_init());
        $request->initRequest($headers, '', $url);
        $result = $request->sendRequest();

        if ($result['info']['http_code'] != 200) {
            return [];
        }

        if (empty($result['data']['lockers'])) {
            return [];
        }

        return $result['data'];
    }

    /**
     * bookBudbeeBoxCarrier
     *
     * @param int $idOrder
     * @param string $orderMessage
     * @param int $employeeId
     * @param string $pdfPath
     * @return void
     * @throws \PrestaShopException
     */
    public function bookBudbeeBoxCarrier(int $idOrder, string $orderMessage, int $employeeId, string $pdfPath)
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
        $boxId = CommonCarrierHelper::getServicePoint($order->id_cart);
        $orderData = BudbeeCarrierHelper::getBudbeeOrderData($order, $boxId);

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
        foreach ($parcelData['parcelIds'] as $parcelId) {
            CommonCarrierHelper::saveShippingNumber($idOrder, $parcelId, $orderMessage, $employeeId);
        }
    }
}
