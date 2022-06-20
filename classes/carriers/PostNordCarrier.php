<?php

namespace Modules\Plugin\KlarnaShippingService\Carrier;

require_once _PS_OVERRIDE_DIR_ . '/modules/klarnaofficial/controllers/front/servicePoints.php';

class PostNordCarrier
{
    /**
     * getAllServicePoints
     *
     * @param string $postalCode
     * @param float $price
     * @return string[]|string[][]
     */
    public static function getAllServicePoints(string $postalCode, float $price = 0): array
    {
        //removed latest selected for now
        /*if ($this->id_customer > 0) {
            $latestServicePoint = $this->getLatestServicePointFromOrder();
        }*/
        $searchResults = self::getPostNordCarrier($postalCode, \Configuration::get('COLLECTORCHECKOUT_POSTNORD_API_TOKEN'));
        $servicePoints = array();
        foreach ($searchResults['servicePoints'] as $searchResult) {
            $servicePoint = array();
            $locationAddress = array();
            $servicePoint['id'] = $searchResult['servicePointId'];
            $servicePoint['name'] = $searchResult['name'];
            $servicePoint['price'] = $price * 100;
            $locationAddress['street_address'] = $searchResult['visitingAddress']['streetName'];
            $locationAddress['postal_code'] = $searchResult['visitingAddress']['postalCode'];
            $locationAddress['city'] = $searchResult['visitingAddress']['city'];
            $servicePoint['address'] = $locationAddress;
            if (isset($latestServicePoint) && $servicePoint['id'] == $latestServicePoint) {
                array_unshift($servicePoints, $servicePoint);
            } else {
                $servicePoints[] = $servicePoint;
            }
        }
        return $servicePoints;
    }

    /**
     * getPostNordCarrier
     *
     * @param mixed $postal_code
     * @param mixed $key
     * @return string[]|string[][]
     */
    private static function getPostNordCarrier(string $postal_code, string $key): array
    {
        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, 'https://api2.postnord.com/rest/businesslocation/v1/servicepoint/findNearestByAddress.json?apikey=' . $key . '&countryCode=SE&postalCode=' . $postal_code);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($handle, CURLOPT_VERBOSE, true);

        $response = json_decode(curl_exec($handle), true);

        return $response['servicePointInformationResponse'];
    }
}
