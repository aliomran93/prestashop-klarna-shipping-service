<?php

namespace PhpDoc\Plugin\KlarnaShippingService;

/**
 * Class ShipmentRequest
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property string $currency
 * @property string $locale
 * @property Order|\stdClass|null $order
 * @property Recipient|\stdClass $recipient
 * @property SelectedShippingOption|\stdClass $selected_shipping_option
 * @property Sender|\stdClass $sender
 * @property string $session_id
 * @property string $selected_shipping_option_id
 */
class ShipmentRequest
{
}

/**
 * Class Order
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property string[] $tags
 * @property string $id
 * @property OrderLine|\stdClass $lines
 * @property int $total_amount
 * @property int $total_tax
 * @property int $total_weight
 */
class Order
{
}

/**
 * Class OrderLine
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property Attribute|\stdClass $attributes
 * @property int $quantity
 * @property string $reference
 * @property int $tax_rate
 * @property int $total_discount_amount
 * @property int $total_price_including_tax
 * @property int $total_tax_amount
 * @property string $type
 * @property int $unit_price
 */
class OrderLine
{
}

/**
 * Class Attribute
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property string[] $tags
 * @property Dimensions $dimensions
 * @property int $weight
 */
class Attribute
{
}

/**
 * Class Dimensions
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property int $height
 * @property int $length
 * @property int $width
 */
class Dimensions
{
}

/**
 * Class Recipient
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property string $care_of
 * @property string $city
 * @property string $company_name
 * @property string $country
 * @property string $customer_type
 * @property string $email
 * @property string $family_name
 * @property string $given_name
 * @property string $phone
 * @property string $postal_code
 * @property string $region
 * @property string $street_address
 * @property string $street_address2
 */
class Recipient
{
}

/**
 * Class Sender
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property Address $address
 * @property string $sender_id
 */
class Sender
{
}

/**
 * Class Address
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property string $city
 * @property string $country
 * @property string $postal_code
 * @property string $region
 * @property string $street_address
 * @property string $street_address2
 */
class Address
{
}

/**
 * Class SelectedShippingOption
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property AddonUserData[]|\stdClass[] $addons
 * @property string $carrier
 * @property string $class
 * @property string $id
 * @property Location|\stdClass $location
 * @property int $price
 * @property int $tax_rate
 * @property TimeSlot|\stdClass $timeslot
 * @property string $type
 */
class SelectedShippingOption
{
}

/**
 * Class AddonUserData
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property UserData|\stdClass $data
 * @property string $id
 * @property int $max_length
 * @property bool $preselected
 * @property int $price
 * @property bool $required
 * @property string $type
 */
class AddonUserData
{
}

/**
 * Class UserData
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property bool $selected
 * @property string $text
 */
class UserData
{
}

/**
 * Class TimeSlot
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property string $end
 * @property string $id
 * @property string $start
 */
class TimeSlot
{
}

/**
 * Class Location
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property Address|\stdClass $address
 * @property Coordinates|\stdClass $coordinates
 * @property string $id
 * @property string name
 */
class Location
{
}

/**
 * Class Coordinates
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property int $lat
 * @property int $lng
 */
class Coordinates
{
}

/**
 * Class AuthRequest
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property string $identifier
 * @property Secret $secret
 */
class AuthRequest
{
}

/**
 * Class Secret
 * @package PhpDoc\Plugin\KlarnaShippingService
 * @property string $digest
 * @property string $nonce
 */
class Secret
{
}
