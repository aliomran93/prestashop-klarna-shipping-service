<?php

namespace Modules\Plugin\KlarnaShippingService;

class ShippingOptionsController extends \ModuleFrontController
{
    /**
     * @throws \PrestaShopException
     */
    public function initContent()
    {
        header("Content-type: application/json");
        if (!KSSAuthentication::validateBearerToken()) {
            header($_SERVER['SERVER_PROTOCOL'] . " 401 Unauthorized");
            die();
        }
        $shippingOptions = [];
        /** @var \PhpDoc\Plugin\KlarnaShippingService\ShipmentRequest $data */
        $data = \ModulesLib::ajaxData(true);
        $preview = empty($data->recipient);
        $tags = json_decode($data->order->tags[0], true);
        $id_cart = (int)$tags['id_cart'];
        $id_lang = $tags['id_lang'];
        if (isset($data->selected_shipping_option_id)) {
            $carrier = new \KSSCarrier($data->selected_shipping_option_id);
            $shippingOptions['shipping_options'][] = $carrier->buildArray($id_cart, $data->recipient);
            die(json_encode($shippingOptions));
        }

        if (!$preview) {
            $this->module->initShipmentId($id_cart);
            $streetAddress =  $data->recipient->street_address;
            $id_customer = (int) (\Customer::customerExists($data->recipient->email, true, true));
        }
        $carriers = \Carrier::getCarriers($id_lang, true);
        foreach ($carriers as $carrier) {
            $KSSCarrier = new \KSSCarrier($carrier['id_carrier']);
            $responseOption = $KSSCarrier->buildArray($id_cart, $data->recipient);
            if (count($responseOption) > 1) {
                $shippingOptions['shipping_options'][] = $responseOption;
            }
        }
        die(json_encode($shippingOptions));
    }
}
