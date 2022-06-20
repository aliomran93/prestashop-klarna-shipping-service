<?php

namespace Modules\Plugin\KlarnaShippingService\Carrier;

require_once _PS_OVERRIDE_DIR_ . '/modules/klarnaofficial/controllers/front/servicePoints.php';
class DHLCarrier
{
    /**
     * getAllServicePoints
     *
     * @param string $postalCode
     * @param float $price
     * @return string[][]|string[]
     */
    public static function getAllServicePoints(string $postalCode, float $price = 0): array
    {
        /*if ($this->id_customer > 0) {
            $latestServicePoint = $this->getLatestServicePointFromOrder();
        }*/
        $locator = new \DhlServicePointLocator(\DhlServicePointLocator::ENDPOINT_PROD);
        $searchResults = $locator->search($postalCode);
        $servicePoints = [];
        foreach ($searchResults as $searchResult) {
            $servicePoint = [];
            $locationAddress = [];
            $servicePoint['id'] = $searchResult['Id'];
            $servicePoint['name'] = $searchResult['DisplayName'];
            $servicePoint['price'] = $price * 100;
            $locationAddress['street_address'] = $searchResult['StreetName'];
            $locationAddress['postal_code'] = $searchResult['PostCode'];
            $locationAddress['city'] = $searchResult['City'];
            $servicePoint['address'] = $locationAddress;
            if (isset($latestServicePoint) && $servicePoint['id'] == $latestServicePoint) {
                array_unshift($servicePoints, $servicePoint);
            } else {
                $servicePoints[] = $servicePoint;
            }
        }
        return $servicePoints;
    }
}
