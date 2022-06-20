<?php

namespace Modules\Plugin\KlarnaShippingService\Helper;

class CommonCarrierHelper
{
    const CARRIER_DHL = 2;
    const CARRIER_POSTNORD = 35;
    const CARRIER_BUDBEE = 64;
    const CARRIER_BUDBEEBOX = 104;
    const CARRIER_INSTABOX = 105;

    /**
     * getServicePoint
     *
     * @param int $cartId
     * @return string
     */
    public static function getServicePoint(int $cartId): string
    {
        $sql = "select distinct servicepointinformation from " . _DB_PREFIX_ . "servicepointinformation WHERE id_cart= " . (int)$cartId;
        $servicePoint = \Db::getInstance()->getValue($sql);
        if ($servicePoint != "") {
            $servicePointId = explode(' | ', $servicePoint, 2)[1];
            return $servicePointId;
        }
    }

    /**
     * saveShippingNumber
     *
     * @param int $id_order
     * @param string $shipping_number
     * @param string $order_message
     * @param int $id_employee
     * @return void
     */
    public static function saveShippingNumber(int $id_order, string $shipping_number, string $order_message, int $id_employee)
    {
        $order = new \Order($id_order);
        $shipping_number = pSQL($shipping_number);
        $order->shipping_number = $shipping_number;
        $order->update();
        $db = \Db::getInstance();
        $sql = new \DbQuery();
        $sql->select('id_order_carrier');
        $sql->from('order_carrier');
        $sql->where('id_order = ' . $id_order);
        $orderCarrierIds = $db->executeS($sql);
        $insertArray = [
            'id_order' => $id_order,
            'tracking_number' => $shipping_number,
            'date_add' => date('Y-m-d H:i:s'),
            'id_carrier' => $order->id_carrier,
            'id_employee' => $id_employee,
            'comment' =>  $order_message
        ];
        $db->insert('order_tracking_number_list', $insertArray);

        if ($orderCarrierIds) {
            foreach ($orderCarrierIds as $orderCarrierId) {
                $orderCarrier = new \OrderCarrier($orderCarrierId['id_order_carrier']);
                $orderCarrier->tracking_number = $shipping_number;
                $orderCarrier->update();
                \Hook::exec('actionAdminOrdersTrackingNumberUpdate', array('order' => $order));
            }
        }
    }
}
