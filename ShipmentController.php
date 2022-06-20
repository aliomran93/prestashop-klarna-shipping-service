<?php

namespace Modules\Plugin\KlarnaShippingService;

use KSSCarrier;
use Modules\Plugin\KlarnaShippingService\carrier\InstaBoxCarrier;

require_once _PS_MODULE_DIR_ . 'klarnashippingservice/classes/carriers/InstaBoxCarrier.php';

/**
 * Class ShipmentController
 * @package Modules\plugin\KlarnaShippingService
 * @property \KlarnaShippingService $module
 */
class ShipmentController extends \ModuleFrontController
{
    public $url_params;

    public function __construct()
    {
        parent::__construct();
        $this->url_params = $this->getUrlParams();
    }

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
        /** @var \PhpDoc\Plugin\KlarnaShippingService\ShipmentRequest $data */
        $data = \ModulesLib::ajaxData(true);
        //Do the same thing for all requests for now.
        if ($this->isConfirmShipmentRequest()) {
            $shipment_id = (int)$this->getShipmentId();
            $this->module->updateConfirmStatus($shipment_id);
            die();
        }

        if ($this->isReserveShipmentRequest() || $this->isUpdateShipmentRequest()) {
            $response = [];
            $tags = json_decode($data->order->tags[0], true);
            $id_cart = (int)$tags['id_cart'];
            $shipment_id = $this->getDbShipmentId($id_cart);
            if ($shipment_id === "") {
                header($_SERVER['SERVER_PROTOCOL'] . " 400 failed");
                die(json_encode(["failure_reason" => "shipping_option_not_available"]));
            }
            //For now, only instabox supports prebooking
            if ($data->selected_shipping_option->carrier === 'instabox') {
                $reservation_token = $this->getReservationToken($id_cart);
                $instaBoxCarrier = new InstaBoxCarrier();
                $error = !$instaBoxCarrier->preBookShipment($reservation_token, $data->selected_shipping_option);
                if ($error) {
                    header($_SERVER['SERVER_PROTOCOL'] . " 400 failed");
                    die(json_encode(["failure_reason" => "shipping_option_not_available"]));
                }
                $this->module->updateReserveStatus($id_cart);
            }
            $selected_shipping_option['carrier'] = $data->selected_shipping_option->carrier;
            $response['shipment_id'] = $shipment_id;
            $response['selected_shipping_option'] = $selected_shipping_option;
            die(json_encode($response));
        }
    }

    /**
     * getShipmentId
     *
     * @return string
     * @throws \PrestaShopException
     */
    private function getShipmentId(): string
    {
        if (empty($this->url_params[0])) {
            return '';
        }
        if ($this->validateShipmentId($this->url_params[0])) {
            return $this->url_params[0];
        }
        throw new \PrestaShopException("Invalid URL params for " . $this->page_name);
    }
    /**
     * validateShipmentId
     *
     * @param  int $shipment_id
     * @return bool
     */
    private function validateShipmentId(int $shipment_id): string
    {
        return preg_match('/[0-9]*/', $shipment_id);
    }
    /**
     * isReserveShipmentRequest
     *
     * @return bool
     */
    public function isReserveShipmentRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    /**
     * isConfirmShipmentRequest
     * POST /shipment/{shipment_id}/confirm
     *
     * @return bool
     */
    public function isConfirmShipmentRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($this->url_params[0]) && $this->url_params[1] === 'confirm';
    }
    /**
     * isUpdateShipmentRequest
     *
     * @return bool
     */
    public function isUpdateShipmentRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'PUT' && !empty($this->url_params[0]);
    }
    private function getUrlParams()
    {
        $url_params = explode('/', $_SERVER['REQUEST_URI']);
        if (count($url_params) < 5) {
            return '';
        }
        return array_slice($url_params, 4);
    }

    /**
     * getDbShipmentId
     *
     * @param int $idCart
     * @return string
     */
    public function getDbShipmentId(int $idCart): string
    {
        $query = new \DbQuery();
        $query->select('shipment_id');
        $query->from('carrier_kss_session');
        $query->where('id_cart=' . $idCart);
        $query->limit(1);
        $query->orderBy('shipment_id DESC');
        $result = \Db::getInstance()->executeS($query);
        return !empty($result) ? $result[0]['shipment_id'] : '';
    }

    /**
     * getReservationToken
     *
     * @param int $idCart
     * @return string
     */
    public function getReservationToken(int $idCart): string
    {
        $query = new \DbQuery();
        $query->select('reservation_token');
        $query->from('carrier_kss_session');
        $query->where('id_cart=' . $idCart);
        $query->limit(1);
        $query->orderBy('shipment_id DESC');
        $result = \Db::getInstance()->executeS($query);
        return !empty($result) ? $result[0]['reservation_token'] : '';
    }
}
