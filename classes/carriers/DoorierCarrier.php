<?php

class DoorierCarrier extends KSSCarrier
{
    private $apiKey = '4b6b17d14ea97fd1dde21bc595ce02a5';
    private $endpoint_url = 'https://api.doorier.se';

    public function getAllServicePoints($deliveryArray)
    {
        return "";
    }

    public function getAllTimeSlots($deliveryArray)
    {
        $response = $this->getDoorierTimeSlots($postalCode);
        if (count($response) > 0) {
            $timeSlots = array();
            foreach ($response as $responseTimeSlot) {
                $timeSlot = array();
                $timeSlot['cutoff'] = $responseTimeSlot['cut_off_date'] . "T" . $responseTimeSlot['cut_off_time'] . "+02:00";
                $timeSlot['start'] = $responseTimeSlot['date'] . "T" . $responseTimeSlot['timeslot_start'] . "+02:00";
                $timeSlot['end'] = $responseTimeSlot['date'] . "T" . $responseTimeSlot['timeslot_end'] . "+02:00";
                $timeSlots[] = $timeSlot;
            }
            return $timeSlots;
        }
        return "";
    }

    public function isCarrierAvailable($deliveryArray)
    {
        if ($this->doorierCheckPostalCode($postalCode) /* && count($this->time_slots) > 1 */) {
            return true;
        }
        return false;
    }

    private function doorierCheckPostalCode($postalCode)
    {
        $url = $this->endpoint_url . "/delivery/checkpostalcode/" . (int)$postalCode;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'x-api-key: ' . $this->apiKey
            )
        );
        $result = json_decode(curl_exec($ch), true);
        if ($result['status'] == 200 && $result['message'] == 'success') {
            return true;
        }
        return false;
    }

    private function getDoorierTimeSlots($postalCode)
    {
        $timeslot_date = strtotime('today');
        $ch = curl_init();
        $result = array();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'x-api-key: ' . $this->apiKey
            )
        );
        $day = 0;
        while ($day < 7) {
            if (date('D', $timeslot_date) == 'Sun') {
                $timeslot_date = strtotime('+1 day', $timeslot_date);
                $day++;
                continue;
            }
            $url = $this->endpoint_url . "/delivery/getslots/" . $postalCode . "?delivery_type=normal&delivery_date=" . date('Y-m-d', $timeslot_date);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = json_decode(curl_exec($ch), true);
            if ($response['message'] == 'success' && count($response['data'])) {
                foreach ($response['data']['normal'] as $timeslot) {
                    $result[] = $timeslot;
                }
            }
            $timeslot_date = strtotime('+1 day', $timeslot_date);
            $day++;
        }
        return $result;
    }

    public static function createDoorierOrder($customer_name, $customer_mobile, $address, $zipcode, $city, $timeslot_date, $timeslot_time, $store_reference)
    {
        $apiKey = '4b6b17d14ea97fd1dde21bc595ce02a5';
        $endpoint_url = 'https://api.doorier.se';
        $order_date = date('Y-m-d');
        if (time() > strtotime('2 pm')) {
            $order_date = date('Y-m-d', strtotime('tomorrow'));
        }
        $data = array(
                'customer_name' => $customer_name,
                'customer_mobile' => str_replace('+46', '0', str_replace(' ', '', $customer_mobile)),
                'address' => $address,
                'postalcode' => $zipcode,
                'city' => $city,
                'delivery_type' => 'normal',
                'delivery_date' => $order_date,
                //'timeslot_end' => $timeslot_time,
                'store_reference' => $store_reference,
                'store_id' => '55',
                'identity_check' => 'yes'
        );
        $url = $endpoint_url . "/delivery/book";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'x-api-key: ' . $apiKey
            )
        );
        $result = json_decode(curl_exec($ch), true);
        if (isset($result['data']['status']) && $result['data']['status'] == true) {
            $shipping_number = pSQL($result['data']['delivery_id']);
            $order = new Order($store_reference);
            $order->shipping_number = $shipping_number;
            $order->update();
            return true;
        } else {
            Logger::addLog('Doorier failed to book delivery. Returned response: ' . json_encode($result), 5, null, 'Order', $store_reference);
            return false;
        }
    }

    public static function saveDeliverySlip($id_order, $pdf_path)
    {
        $order = new Order($id_order);
        $apiKey = '4b6b17d14ea97fd1dde21bc595ce02a5';
        $half = strlen($apiKey) / 2;
        $first_half = substr($apiKey, 0, $half);
        $second_half = substr($apiKey, $half);

        $skey = $second_half . base64_encode($id_order . $apiKey) . $first_half;

        $url = "http://api.doorier.se/deliveries/printlabel?id=" . $id_order . '&skey=' . $skey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        if ($info['http_code'] == 200) {
            $fh = fopen($pdf_path, 'w') or die("can't open file (150) $pdf_path");
            fwrite($fh, $result);
            fclose($fh);
            return true;
        }
        return false;
    }
}
