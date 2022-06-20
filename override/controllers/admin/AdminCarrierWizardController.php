<?php

class AdminCarrierWizardController extends AdminCarrierWizardControllerCore
{
    public function renderStepOne($carrier)
    {
        $this->fields_form = array(
            'form' => array(
                'id_form' => 'step_carrier_general',
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Carrier name'),
                        'name' => 'name',
                        'required' => true,
                        'hint' => array(
                            sprintf($this->l('Allowed characters: letters, spaces and "%s".'), '().-'),
                            $this->l('The carrier\'s name will be displayed during checkout.'),
                            $this->l('For in-store pickup, enter 0 to replace the carrier name with your shop name.')
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Transit time'),
                        'name' => 'delay',
                        'lang' => true,
                        'required' => true,
                        'maxlength' => 512,
                        'hint' => $this->l('The estimated delivery time will be displayed during checkout.')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Speed grade'),
                        'name' => 'grade',
                        'required' => false,
                        'size' => 1,
                        'hint' => $this->l('Enter "0" for a longest shipping delay, or "9" for the shortest shipping delay.')
                    ),
                    array(
                        'type' => 'logo',
                        'label' => $this->l('Logo'),
                        'name' => 'logo'
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Tracking URL'),
                        'name' => 'url',
                        'hint' => $this->l('Delivery tracking URL: Type \'@\' where the tracking number should appear. It will be automatically replaced by the tracking number.'),
                        'desc' => $this->l('For example: \'http://example.com/track.php?num=@\' with \'@\' where the tracking number should appear.')
                    ),
                    array(
                        'type' => 'kss_section',
                    )
                )
            )
        );
        $kssCarrier = new \KSSCarrier($carrier->id);
        $carrierFeatures = $kssCarrier->getFeatures();
        $tpl_vars = array(
            'max_image_size' => (int)Configuration::get('PS_PRODUCT_PICTURE_MAX_SIZE') / 1024 / 1024,
            'shipping_name' => $kssCarrier->shipping_name,
            'shipping_type' => $kssCarrier->shipping_type,
            'shipping_class' => $kssCarrier->shipping_class,
            'shipping_features' => $carrierFeatures ?? false ?: []
        );
        $fields_value = $this->getStepOneFieldsValues($carrier);
        return $this->renderGenericForm(array('form' => $this->fields_form), $fields_value, $tpl_vars);
    }

    protected function validateForm($die = true)
    {
        $step_number = (int)Tools::getValue('step_number');
        $return = array('has_error' => false);

        if (!$this->tabAccess['edit']) {
            $this->errors[] = Tools::displayError('You do not have permission to use this wizard.');
        } else {
            if (Shop::isFeatureActive() && $step_number == 2) {
                if (!Tools::getValue('checkBoxShopAsso_carrier')) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('You must choose at least one shop or group shop.');
                }
            } else {
                $this->validateRules();
                if (Module::isEnabled('klarnashippingservice')) {
                    if ($step_number == 1) {
                        $carrierName = Tools::getValue('shipping_name');
                        if (!$carrierName || $carrierName === '') {
                            $this->errors[] = $this->l('KSS shipping name cannot be empty.');
                        }
                        $featureUrl = Tools::getValue('feature_url');
                        if ($featureUrl && $featureUrl === '') {
                            $this->errors[] = $this->l('Feature URL is not set.');
                        }
                        $kssFeatuers = \Tools::getValue('kss_features');

                        $featureClassCount = array_count_values(array_column($kssFeatuers, 'feature_class'));
                        if (isset($featureClassCount['call_to_action']) && $featureClassCount['call_to_action'] > 2) {
                            $this->errors[] = $this->l('You can have a maximum of 2 call to action features.');
                        }
                        if (isset($featureClassCount['badge']) && $featureClassCount['badge'] > 5) {
                            $this->errors[] = $this->l('You can have a maximum of 5 badge features.');
                        }
                        if (isset($featureClassCount['info']) && $featureClassCount['info'] > 2) {
                            $this->errors[] = $this->l('You can have a maximum of 2 info features.');
                        }
                        $featureTypeCount = array_count_values(array_column($kssFeatuers, 'feature_type'));
                        foreach ($featureTypeCount as $amount) {
                            if ($amount > 1) {
                                $this->errors[] = $this->l('Featur types are not unique.');
                            }
                        }
                    }
                }
            }
        }

        if (count($this->errors)) {
            $return['has_error'] = true;
            $return['errors'] = $this->errors;
        }
        if (count($this->errors) || $die) {
            die(Tools::jsonEncode($return));
        }
    }

    public function ajaxProcessFinishStep()
    {
        $return = array('has_error' => false);
        if (!$this->tabAccess['edit']) {
            $return = array(
                'has_error' =>  true,
                $return['errors'][] = Tools::displayError('You do not have permission to use this wizard.')
            );
        } else {
            $this->validateForm(false);
            if ($id_carrier = Tools::getValue('id_carrier')) {
                $current_carrier = new Carrier((int)$id_carrier);

                // if update we duplicate current Carrier
                /** @var Carrier $new_carrier */
                $new_carrier = $current_carrier->duplicateObject();

                if (Validate::isLoadedObject($new_carrier)) {
                    // Set flag deteled to true for historization
                    $current_carrier->deleted = true;
                    $current_carrier->update();

                    // Fill the new carrier object
                    $this->copyFromPost($new_carrier, $this->table);
                    $new_carrier->position = $current_carrier->position;
                    $new_carrier->update();
                    $this->updateAssoShop((int)$new_carrier->id);
                    $this->duplicateLogo((int)$new_carrier->id, (int)$current_carrier->id);
                    $this->changeGroups((int)$new_carrier->id);

                    //Copy default carrier
                    if (Configuration::get('PS_CARRIER_DEFAULT') == $current_carrier->id) {
                        Configuration::updateValue('PS_CARRIER_DEFAULT', (int)$new_carrier->id);
                    }

                    // Call of hooks
                    Hook::exec('actionCarrierUpdate', array(
                            'id_carrier' => (int)$current_carrier->id,
                            'carrier' => $new_carrier
                        ));
                    $this->postImage($new_carrier->id);
                    $this->changeZones($new_carrier->id);
                    $new_carrier->setTaxRulesGroup((int)Tools::getValue('id_tax_rules_group'));
                    $carrier = $new_carrier;

                    if (\Module::isEnabled('klarnashippingservice')) {
                        $kssCarrier = new \KSSCarrier();
                        $kssCarrier->id = $carrier->id;
                        $kssCarrier->force_id = true;
                        $kssCarrier->shipping_name = Tools::getValue('shipping_name');
                        $kssCarrier->shipping_type = Tools::getValue('shipping_type');
                        $kssCarrier->shipping_class = Tools::getValue('shipping_class');
                        $kssCarrier->add();
                        $shippingFeatures = \Tools::getValue('kss_features');
                        if (!empty($shippingFeatures)) {
                            foreach ($shippingFeatures as $feature) {
                                $feature['id_carrier'] = $carrier->id;
                                $kssCarrier->setFeature($feature);
                            }
                        }
                    }
                }
            } else {
                $carrier = new Carrier();
                $this->copyFromPost($carrier, $this->table);
                if (!$carrier->add()) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving this carrier.');
                }
            }

            if ($carrier->is_free) {
                //if carrier is free delete shipping cost
                $carrier->deleteDeliveryPrice('range_weight');
                $carrier->deleteDeliveryPrice('range_price');
            }

            if (Validate::isLoadedObject($carrier)) {
                if (!$this->changeGroups((int)$carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving carrier groups.');
                }

                if (!$this->changeZones((int)$carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving carrier zones.');
                }

                if (!$carrier->is_free) {
                    if (!$this->processRanges((int)$carrier->id)) {
                        $return['has_error'] = true;
                        $return['errors'][] = $this->l('An error occurred while saving carrier ranges.');
                    }
                }

                if (Shop::isFeatureActive() && !$this->updateAssoShop((int)$carrier->id)) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving associations of shops.');
                }

                if (!$carrier->setTaxRulesGroup((int)Tools::getValue('id_tax_rules_group'))) {
                    $return['has_error'] = true;
                    $return['errors'][] = $this->l('An error occurred while saving the tax rules group.');
                }

                if (Tools::getValue('logo')) {
                    if (Tools::getValue('logo') == 'null' && file_exists(_PS_SHIP_IMG_DIR_ . $carrier->id . '.jpg')) {
                        unlink(_PS_SHIP_IMG_DIR_ . $carrier->id . '.jpg');
                    } else {
                        $logo = basename(Tools::getValue('logo'));
                        if (!file_exists(_PS_TMP_IMG_DIR_ . $logo) || !copy(_PS_TMP_IMG_DIR_ . $logo, _PS_SHIP_IMG_DIR_ . $carrier->id . '.jpg')) {
                            $return['has_error'] = true;
                            $return['errors'][] = $this->l('An error occurred while saving carrier logo.');
                        }
                    }
                }
                $return['id_carrier'] = $carrier->id;
            }
        }
        die(Tools::jsonEncode($return));
    }
}
