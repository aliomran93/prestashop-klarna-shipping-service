<?php

require_once dirname(__DIR__) . 'APIClient.php';

class PostNordBrevCarrier
{
    public function getAvailability()
    {
        $apiClient = new \Modules\plugin\KlarnaShippingService\APIClient(curl_init());
        $apiClient->initRequest();
    }
}