<?php

class DefaultCarrier extends KSSCarrier
{

    public function getAllServicePoints($postalCode)
    {
        return "";
    }

    public function isCarrierAvailable($deliveryArray = array())
    {
        return "";
    }

    public function getAllTimeSlots($customerData)
    {
        return "";
    }
}
