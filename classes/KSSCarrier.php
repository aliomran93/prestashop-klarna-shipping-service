<?php

require_once 'carriers/DHLCarrier.php';
require_once 'carriers/BudbeeBoxCarrier.php';
require_once 'carriers/BudbeeCarrier.php';
require_once 'carriers/PostNordCarrier.php';
require_once 'carriers/InstaBoxCarrier.php';

use Modules\Plugin\KlarnaShippingService\Carrier\BudbeeBoxCarrier;
use Modules\Plugin\KlarnaShippingService\Carrier\BudbeeCarrier;
use Modules\Plugin\KlarnaShippingService\Carrier\DHLCarrier;
use Modules\Plugin\KlarnaShippingService\Carrier\InstaBoxCarrier;
use Modules\Plugin\KlarnaShippingService\Carrier\PostNordCarrier;

//namespace Modules\Plugin\KlarnaShippingService;

class KSSCarrier extends \ObjectModel
{
    public $id;
    public $shipping_type;
    public $carrier;
    public $shipping_name;
    public $deliveryTime;
    public $shipping_class;

    const SHIPPING_TYPES = [
        'pickup-box',
        'delivery-address',
        'pickup-point'
    ];

    public static $definition = array(
        'table' => 'carrier_kss',
        'primary' => 'id_carrier',
        'fields' => array(
            'shipping_type' => array('type' => self::TYPE_STRING),
            'shipping_name' => array('type' => self::TYPE_STRING),
            'shipping_class' => array('type' => self::TYPE_STRING)
        )
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        $this->carrier = new \Carrier($id);
    }

    /**
     * getFeatures
     *
     * @return string[]|array
     */
    public function getFeatures(): array
    {
        $sql = new \DbQuery();
        $sql->select('*');
        $sql->from('carrier_kss_feature');
        $sql->where('id_carrier = ' . $this->id);
        $result = \Db::getInstance()->executeS($sql);
        if (!$result) {
            return [];
        }
        return $result;
    }

    /**
     * setFeature
     *
     * @param string[] $feature
     * @return void
     */
    public function setFeature(array $feature)
    {
        if (!is_array($feature) || empty($feature)) {
            return;
        }
        if (empty($feature['feature_url'])) {
            $feature['feature_url'] = '';
        }
        Db::getInstance()->insert('carrier_kss_feature', $feature);
    }

    /**
     * getShippingPrice
     *
     * @param  int $id_cart
     * @param  bool $inc_tax
     * @return float
     */
    public function getShippingPrice(int $id_cart, bool $inc_tax = true): float
    {
        $cart = new \Cart($id_cart);
        if (!isset($cart->id)) {
            throw new PrestaShopException('Invalid cart used to get klarnashippingservice price');
        }
        foreach ($cart->getCartRules() as $rule) {
            if ($rule['free_shipping']) {
                return 0;
            }
        }
        if ($this->carrier->shipping_method == \Carrier::SHIPPING_METHOD_FREE) {
            return 0;
        } else {
            $configuration = \Configuration::getMultiple(
                [
                    'PS_SHIPPING_FREE_PRICE',
                    'PS_SHIPPING_FREE_WEIGHT'
                ]
            );

            $cartTotalprice = $cart->getOrderTotal(true, \Cart::BOTH_WITHOUT_SHIPPING);
            if ($configuration['PS_SHIPPING_FREE_PRICE'] && $cartTotalprice >= $configuration['PS_SHIPPING_FREE_PRICE']) {
                return 0;
            }

            $cartTotalWeight = $cart->getTotalWeight();
            if ($configuration['PS_SHIPPING_FREE_WEIGHT'] && $cartTotalWeight >= $configuration['PS_SHIPPING_FREE_WEIGHT']) {
                return 0;
            }

            $address_delivery = new \Address($cart->id_address_delivery);
            $taxManager = $this->carrier->getTaxCalculator(new \Address($cart->id_address_delivery));
            if ($this->carrier->shipping_method == \Carrier::SHIPPING_METHOD_WEIGHT) {
                $price_exc_tax = $this->carrier->getDeliveryPriceByWeight($cartTotalWeight, \Address::getZoneById($address_delivery->id));
            } elseif ($this->carrier->shipping_method == \Carrier::SHIPPING_METHOD_PRICE) {
                $price_exc_tax = $this->carrier->getDeliveryPriceByPrice($cartTotalprice, \Address::getZoneById($address_delivery->id));
            }
            if (!$inc_tax) {
                if (!$price_exc_tax) {
                    return 0;
                }
                return $price_exc_tax;
            }
            if ($price_exc_tax > 0) {
                $price_inc_tax = $taxManager->getTaxesAmount($price_exc_tax)[$this->carrier->getIdTaxRulesGroupByIdCarrier($this->carrier->id)] + $price_exc_tax;
                return $price_inc_tax;
            } else {
                return 0;
            }
        }
    }

    /**
     * buildArray
     *
     * @param int $idCart
     * @param \PhpDoc\Plugin\KlarnaShippingService\Recipient|\stdClass $recipientDeliveryAdress
     * @return array
     * @throws PrestaShopException
     */
    public function buildArray(int $idCart, $recipientDeliveryAdress): array
    {
        $shippingPriceTacExc = $this->getShippingPrice($idCart, false);
        $shippingPriceTaxInc = $this->getShippingPrice($idCart);
        $taxRate = $shippingPriceTacExc > 0 ? round($shippingPriceTaxInc / $shippingPriceTacExc - 1, 2) : 0;
        $pickupLocations = [];
        $carrierPreview = !isset($recipientDeliveryAdress->postal_code);
        $carrierAvailable = false;
        if (!$carrierPreview) {
            $postalCode = str_replace(' ', '', $recipientDeliveryAdress->postal_code);
            switch ($this->shipping_name) {
                case 'dhl':
                    $pickupLocations = DHLCarrier::getAllServicePoints($postalCode, $shippingPriceTaxInc);
                    $carrierAvailable = !empty($pickupLocations);
                    break;
                case 'postnord':
                    $pickupLocations = PostNordCarrier::getAllServicePoints($postalCode, $shippingPriceTaxInc);
                    $carrierAvailable = !empty($pickupLocations);
                    break;
                case 'budbee':
                    $carrierAvailable = BudbeeCarrier::isCarrierAvailable($postalCode);
                    break;
                case 'budbee-box':
                    $pickupLocations = BudbeeBoxCarrier::getAllServicePoints($postalCode, $shippingPriceTaxInc);
                    $carrierAvailable = BudbeeBoxCarrier::isCarrierAvailable($idCart);
                    break;
                case 'instabox':
                    $pickupLocations = InstaBoxCarrier::getAllServicePoints($recipientDeliveryAdress, $shippingPriceTaxInc);
                    //TODO: borrowing budbees check. Make a new check that is dynamic that works for both
                    $carrierAvailable = !empty($pickupLocations) && BudbeeBoxCarrier::isCarrierAvailable($idCart);
                    if ($carrierAvailable) {
                        self::insertReservationId($idCart, $pickupLocations['availability_token']);
                        unset($pickupLocations['availability_token']);
                    }
                    break;
            }
        }
        if (($carrierAvailable || $carrierPreview) && isset($this->id)) {
            $features = $this->getFeatures();
            if (!empty($features)) {
                foreach ($features as $feature) {
                    $carrier['features'][] = array_filter([
                        'class' => $feature['feature_class'],
                        'type' => $feature['feature_type'],
                        'url' => $feature['feature_url']
                    ]);
                }
            }
            $carrier['id'] = $this->id;
            $carrier['type'] = $this->shipping_type;
            $carrier['carrier'] = $this->shipping_name;
            $carrier['name'] = $this->shipping_name;
            $carrier['price'] = $shippingPriceTaxInc * 100;
            $carrier['tax_rate'] = $taxRate * 100;
            $carrier['delivery_time'] = $this->getDeliveryTime();
            $carrier['class'] = $this->shipping_class;
            $carrier['preview'] = $carrierPreview;
            if (!empty($pickupLocations)) {
                $carrier['locations'] = $pickupLocations;
            }
            return $carrier;
        }
        return [];
    }

    /**
     * insertReservationId
     *
     * @param  int $idCart
     * @param  string $availabilityToken
     * @return void
     */
    public static function insertReservationId(int $idCart, string $availabilityToken)
    {
        $sql = "UPDATE " . _DB_PREFIX_ . "carrier_kss_session SET reservation_token='{$availabilityToken}' WHERE id_cart={$idCart} AND status='INIT' ORDER BY shipment_id DESC LIMIT 1";
        Db::getInstance()->execute($sql);
    }

    /**
     * getDeliveryTime
     *
     * @return string[][]
     */
    public function getDeliveryTime()
    {
        $days = str_replace(" dagar", "", $this->carrier->delay[1]);
        if (!$days) {
            return [];
        }
        $delay = explode("-", $days, 2);
        if (empty($delay[1])) {
            return [
                'interval' => [
                    'earliest' => $delay[0],
                    'latest' => $delay[0]
                ]
            ];
        }
        return [
            'interval' => [
                'earliest' => $delay[0],
                'latest' => $delay[1]
            ]
        ];
    }

    /**
     * getLatestServicePointFromOrder
     * Doesn't work atm
     * @return string
     */
    public function getLatestServicePointFromOrder()
    {
        $sql = 'SELECT spi.servicepointinformation FROM `'
                . _DB_PREFIX_ . 'orders` o '
                . 'LEFT JOIN `' . _DB_PREFIX_ . 'servicepointinformation` spi ON o.id_cart = spi.id_cart '
                . 'WHERE o.id_customer = ' . $this->id_customer . ' AND o.id_carrier = ' . $this->id . ' AND spi.postalcode = ' . $this->deliveryPostalCode . ' ORDER BY o.id_order DESC LIMIT 1';
        $result = \Db::getInstance()->executeS($sql);
        if (count($result)) {
            $returnStr = substr($result[0]['servicepointinformation'], strpos($result[0]['servicepointinformation'], '|') + 2);
            return $returnStr;
        }
        return '';
    }
}
