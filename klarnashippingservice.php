<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/ShipmentController.php';
require_once __DIR__ . '/ShippingOptionsController.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/classes/KSSAuthentication.php';
require_once __DIR__ . '/classes/KSSCarrier.php';

class KlarnaShippingService extends Module
{
    const BUDBEE_BOX_REFERENCE = 104;
    const BUDBEE_REFERENCE = 64;
    const DHL_REFERENCE = 2;
    const INSTABOX_REFERENCE = 105;
    const POSTNORD_REFERENCE = 35;

    const MODULE_TEMPLATES =  [
        'carrier_wizard' => [
            'path' => 'controllers/admin/templates/carrier_wizard/helpers/form/form.tpl',
            'override_block_type' => 'kss_section'
        ]
    ];

    public function __construct()
    {
        $this->name = 'klarnashippingservice';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Ali Omran';
        $this->need_instance = 0;

        $this->bootstrap = true;

        if (!is_array($this->dependencies)) {
            $this->dependencies = [];
        }
        $this->dependencies[] = 'attribute_unity';

        parent::__construct();

        $this->displayName = $this->l('Klarna Shipping Service');
        $this->description = $this->l('Klarna Shipping Service direct integration');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('moduleRoutes') &&
            $this->installCustomAdminThemes() &&
            $this->initKssTable() &&
            $this->initKssFeatureTable() &&
            $this->initKssSessoinTable();
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallCustomAdminThemes();
    }

    public function hookDisplayBackOfficeHeader()
    {
        if ($this->context->controller instanceof AdminCarrierWizardController || $this->context->controller instanceof AdminCarrierWizardControllerCore) {
            $this->context->controller->addJS(_PS_THEME_DIR_ . '/js/autoload/jsx.js');
            $localPath = $this->getLocalPath();
            $this->context->controller->addJS($localPath . '/ts/carrier_setup.js');
            $this->context->controller->addCSS($localPath . '/less/admin_carrier.css');
        }
    }

    public function hookModuleRoutes()
    {
        return [
            'module-klarnashippingservice-shipment' => [
                'controller' => 'shipment',
                'rule' => 'modules/klarnashippingservice/shipment{/:shipment_id}{/:confirm}',
                'keywords' => [
                    'shipment_id' => ['regexp' => '[_a-zA-Z0-9_-]+'],
                    'confirm' => ['regexp' => 'confirm']
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => 'klarnashippingservice'
                ],
            ],
            'module-klarnashippingservice-shippingoptions' => [
                'controller' => 'shippingoptions',
                'rule' => 'modules/klarnashippingservice/shippingoptions',
                'params' => [
                    'fc' => 'module',
                    'module' => 'klarnashippingservice'
                ],
            ],
        ];
    }

    /**
     * initKssTable
     *
     * @return bool
     */
    private function initKssTable(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'carrier_kss(
            id_carrier int(11) NOT NULL,
            shipping_type VARCHAR(32) NOT NULL,
            shipping_name VARCHAR(32) NOT NULL,
            shipping_class VARCHAR(32) NOT NULL,
            PRIMARY KEY(id_carrier)
        )';
        return Db::getInstance()->execute($sql);
    }

    /**
     * initKssFeatureTable
     *
     * @return bool
     */
    private function initKssFeatureTable(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'carrier_kss_feature(
            id_feature int(11) NOT NULL AUTO_INCREMENT,
            id_carrier int(11) NOT NULL,
            feature_class VARCHAR(32) NOT NULL,
            feature_type VARCHAR(32) NOT NULL,
            feature_url VARCHAR(128),
            PRIMARY KEY(id_feature)
        )';
        return Db::getInstance()->execute($sql);
    }

    /**
     * initKssSessoinTable
     *
     * @return bool
     */
    private function initKssSessoinTable(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'carrier_kss_session(
            shipment_id int(11) NOT NULL AUTO_INCREMENT,
            id_cart int(11) NOT NULL,
            status VARCHAR(32) NOT NULL,
            reservation_token VARCHAR(255),
            date_init DATETIME NOT NULL,
            PRIMARY KEY(shipment_id)
        )';
        return Db::getInstance()->execute($sql);
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitKSSConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * getConfigForm
     *
     * @return string[]|string[][]|string[][][]
     */
    protected function getConfigForm(): array
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'KSS_IDENTIFIER',
                        'label' => $this->l('KSS Identifier'),
                    ),
                     array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'KSS_SECRET',
                        'label' => $this->l('KSS Secret'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'KSS_HASHING_SECRET',
                        'label' => $this->l('KSS Hashing secret'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'INSTABOX_CLIENT_ID',
                        'label' => $this->l('Instabox client ID'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'INSTABOX_SECRET_KEY',
                        'label' => $this->l('Instabox secret key'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'BUDBEE_CLIENT_ID',
                        'label' => $this->l('Budbee client ID'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'BUDBEE_SECRET_KEY',
                        'label' => $this->l('Budbee secret key'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'BUDBEE_COLLECTION_ID',
                        'label' => $this->l('Budbee Collection ID'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * getConfigFormValues
     *
     * @return string[]
     */
    protected function getConfigFormValues(): array
    {
        return [
            'KSS_IDENTIFIER' => Configuration::get('KSS_IDENTIFIER'),
            'KSS_SECRET' => Configuration::get('KSS_SECRET'),
            'KSS_HASHING_SECRET' => Configuration::get('KSS_HASHING_SECRET'),
            'INSTABOX_CLIENT_ID' => Configuration::get('INSTABOX_CLIENT_ID'),
            'INSTABOX_SECRET_KEY' => Configuration::get('INSTABOX_SECRET_KEY'),
            'BUDBEE_CLIENT_ID' => Configuration::get('BUDBEE_CLIENT_ID'),
            'BUDBEE_SECRET_KEY' => Configuration::get('BUDBEE_SECRET_KEY'),
            'BUDBEE_COLLECTION_ID' => Configuration::get('BUDBEE_COLLECTION_ID'),
        ];
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitKSSConfig')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        return $this->renderForm();
    }

    /**
     * installCustomAdminThemes
     *
     * @return bool
     */
    public function installCustomAdminThemes(): bool
    {
        foreach (self::MODULE_TEMPLATES as $key => $module_template) {
            $ps_override_file = _PS_OVERRIDE_DIR_ . $module_template['path'];
            if (file_exists(dirname($ps_override_file)) && !is_writable(dirname($ps_override_file))) {
                throw new Exception(Tools::displayError('Override file ' . dirname($ps_override_file) . ' is not writable'));
            }
            $module_override_file = $this->getLocalPath() . 'override/' . $module_template['path'];
            $override_file_content = file_get_contents($module_override_file);
            $override_content_pattern = '/{block(\s.*)?}.*\$input\.type(\s?)*==(\s?)*(\'|\")' . $module_template['override_block_type'] . '(\'|\").*{\/block}/s';
            $clean_override_content = array();
            preg_match($override_content_pattern, $override_file_content, $clean_override_content);
            if (!file_exists($ps_override_file) && $clean_override_content && count($clean_override_content)) {
                $dir_name = dirname($ps_override_file);
                if (!is_dir($dir_name)) {
                    $oldumask = umask(0000);
                    @mkdir(dirname($ps_override_file), 0777, true);
                    umask($oldumask);
                }
                if (!is_writable($dir_name)) {
                    throw new Exception('Directory ' . $dir_name . ' is not writable');
                }
                $header_content = "{extends file=\"helpers/form/form.tpl\"}\n";
                file_put_contents($ps_override_file, $header_content . $clean_override_content[0] . "\n");
            } else {
                if ($clean_override_content && count($clean_override_content)) {
                    file_put_contents($ps_override_file, $clean_override_content[0] . "\n", FILE_APPEND);
                }
            }
        }
        return true;
    }

    /**
     * uninstallCustomAdminThemes
     *
     * @return true
     */
    public function uninstallCustomAdminThemes()
    {
        foreach (self::MODULE_TEMPLATES as $key => $module_template) {
            $ps_override_file = _PS_OVERRIDE_DIR_ . $module_template['path'];
            //If file was removed for some reason, return success install
            if (!file_exists($ps_override_file)) {
                continue;
            }
            if (file_exists($ps_override_file) && !is_writable($ps_override_file)) {
                throw new Exception(Tools::displayError('Override file ' . dirname($ps_override_file) . ' is not writable'));
            }
            $ps_file_content = file_get_contents($ps_override_file);
            $override_content_pattern = '/{block(\s.*)?}(.*?)\$input\.type(\s?)*==(\s?)*(\'|\")' . $module_template['override_block_type'] . '(\'|\")(.*?){\/block}/s';
            $clean_ps_override_content = preg_replace($override_content_pattern, '', $ps_file_content);
            $ps_content_header_excluded = preg_replace('/{extends file=\"helpers\/form\/form.tpl\"}/s', '', $clean_ps_override_content);
            if (strlen(trim($ps_content_header_excluded))) {
                file_put_contents($ps_override_file, $clean_ps_override_content);
            } else {
                unlink($ps_override_file);
            }
        }
        return true;
    }

    /**
     * initShipmentId
     *
     * @param int $id_cart
     * @return bool
     */
    public function initShipmentId(int $id_cart): bool
    {
        $sql = "INSERT INTO " . _DB_PREFIX_ . "carrier_kss_session(id_cart, status, reservation_token, date_init) VALUES({$id_cart}, 'INIT', '', now())";
        $result = \Db::getInstance()->execute($sql);
        return $result;
    }

    /**
     * updateReserveStatus
     *
     * @param int $idCart
     * @return bool
     */
    public function updateReserveStatus(int $idCart): bool
    {
        $sql = "UPDATE " . _DB_PREFIX_ . "carrier_kss_session SET status='RESERVED' WHERE id_cart = " . $idCart . " ORDER BY shipment_id DESC LIMIT 1";
        $result = \Db::getInstance()->execute($sql);
        return $result;
    }

    /**
     * updateConfirmStatus
     *
     * @param int $shipment_id
     * @return bool
     */
    public function updateConfirmStatus(int $shipment_id): bool
    {
        $sql = "UPDATE " . _DB_PREFIX_ . "carrier_kss_session SET status='CONFIRMED' WHERE shipment_id = " . $shipment_id;
        $result = \Db::getInstance()->execute($sql);
        return $result;
    }
}
