<?php

require_once _PS_OVERRIDE_DIR_ . '/modules/klarnaofficial/controllers/front/servicePoints.php';

class EarlyBirdCarrier extends KSSCarrier
{

    public function getAllServicePoints($deliveryArray)
    {
        return "";
    }

    public function isCarrierAvailable($deliveryArray)
    {
        $MTDResponse = $this->MTDaddressLookup($postalCode, $deliveryAddress);
        if (!empty($MTDResponse[0]['Felmeddelande'])) {
            return false;
        }
        return true;
    }

    public function MTDaddressLookup($postcode, $address)
    {
        $data = array(
            'Gata' => $address,
            'PostNr' => $postcode,
            'Datum' => Date("Y-m-d"),
            'KundNr' => 10338
        );

        $headers = array(
        'Accept: application/json'
        );

        $url = "https://axelny.mtd.se/api/Transport/?" . http_build_query($data);


        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_USERPWD, "10338:B7&gd9Jri");
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query(array()));
        $response = curl_exec($handle);

        return Tools::jsonDecode($response, true);
    }

    public function getAllTimeSlots($customerData)
    {
        return "";
    }
}
