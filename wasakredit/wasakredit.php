<?php
/**
 * 2008 - 2022 Wasa Kredit B2B
 *
 * MODULE Wasa Kredit
 *
 * @author    Wasa Kredit AB
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
 * @version   1.0.0
 * @link      http://www.wasakredit.se
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require _PS_MODULE_DIR_.'wasakredit/vendor/wasa/client-php-sdk/Wasa.php';
require_once _PS_MODULE_DIR_.'wasakredit/utility/SdkHelper.php';

class WasaKredit extends PaymentModule
{
    private $html = '';
    private $postErrors = [];

    public $LEASING_ENABLED;
    public $INVOICE_ENABLED;

    public function __construct()
    {
        $this->name = 'wasakredit';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.0';
        $this->author = 'Wasa Kredit AB';
        $this->need_instance = 0;

        $this->controllers = ['leasing', 'invoice', 'callback', 'confirm'];
        $this->module_key = 'cbeeaf12d953737cdfc75636d737286a';
        $this->bootstrap = true;

        $this->_client = Wasa_Kredit_Checkout_SdkHelper::CreateClient();

        parent::__construct();

        $this->displayName = $this->l('Wasa Kredit B2B');
        $this->description = $this->l('The Wasa Kredit B2B checkout.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99');
    }

    public function install()
    {
        Configuration::updateValue('WASAKREDIT_TEST', false);
        Configuration::updateValue('WASAKREDIT_CLIENTID', '');
        Configuration::updateValue('WASAKREDIT_CLIENTSECRET', '');
        Configuration::updateValue('WASAKREDIT_TEST_CLIENTID', '');
        Configuration::updateValue('WASAKREDIT_TEST_CLIENTSECRET', '');
        Configuration::updateValue('WASAKREDIT_LEASING_ENABLED', '');
        Configuration::updateValue('WASAKREDIT_INVOICE_ENABLED', '');

        return parent::install()
            && $this->registerHook('header')
            && $this->registerHook('payment')
            && $this->registerHook('paymentReturn')
            && $this->registerHook('displayProductButtons')
            && $this->registerHook('actionPaymentConfirmation')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayPaymentReturn');
    }

    public function uninstall()
    {
        return Configuration::deleteByName('WASAKREDIT_CLIENTID')
            && Configuration::deleteByName('WASAKREDIT_CLIENTSECRET')
            && Configuration::deleteByName('WASAKREDIT_TEST')
            && Configuration::deleteByName('WASAKREDIT_TEST_CLIENTID')
            && Configuration::deleteByName('WASAKREDIT_TEST_CLIENTSECRET')
            && Configuration::deleteByName('WASAKREDIT_LEASING_ENABLED')
            && Configuration::deleteByName('WASAKREDIT_INVOICE_ENABLED')
            && parent::uninstall();
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitWasakreditModule') == true) {
            $this->postProcess();
        }

        return $this->renderForm();
    }

    private function postProcess()
    {
        $values = $this->getConfigValues();

        foreach (array_keys($values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookdisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path.'views/css/wasakredit.css');
        $this->context->controller->addJS($this->_path.'views/js/wasakredit.js');
    }

    public function validate_leasing_amount($amount)
    {
        return $this->_client
            ->validate_financed_amount($amount)
            ->data['validation_result'];
    }

    public function validate_invoice_amount($amount)
    {
        return $this->_client
            ->validate_financed_invoice_amount($amount)
            ->data['validation_result'];
    }

    public function getLeasingPaymentOptions($params)
    {
        $cart = new Cart($this->context->cookie->id_cart);
        $amount = $cart->getOrderTotal();

        $response = $this->_client->get_leasing_payment_options($amount);

        $contracts = (!empty($response->data) && !empty($response->data['contract_lengths']))
            ? $response->data['contract_lengths']
            : [];

        $cost = null;
        $length = null;

        foreach ($contracts as $contract) {
            if (empty($contract['monthly_cost']['amount']) || !is_numeric($contract['monthly_cost']['amount'])) {
                continue;
            }

            if (is_null($cost) || $contract['monthly_cost']['amount'] <= $cost) {
                $cost = $contract['monthly_cost']['amount'];
                $length = $contract['contract_length'];
            }
        }

        return is_numeric($cost)
            ? sprintf('Leasing från %s kr/mån i %s månader', $cost, $length)
            : 'Finansiera ditt köp med Wasa Kredit';
    }

    public function hookPayment($params)
    {
        $cart = new Cart($this->context->cookie->id_cart);
        $amount = $cart->getOrderTotal(false);

        $methods = [];

        if (Configuration::get('WASAKREDIT_LEASING_ENABLED')) {
            $methods[] = [
                'text'  => 'Wasa Kredit Leasing',
                'extra' => $this->getLeasingPaymentOptions($params),
                'link'  => $this->context->link->getModuleLink($this->name, 'leasing', [], true),
            ];
        }

        if (Configuration::get('WASAKREDIT_INVOICE_ENABLED')) {
            $methods[] = [
                'text'  => 'Wasa Kredit Faktura',
                'extra' => 'Betala tryggt 30 dagar efter att du fått din leverans',
                'link'  => $this->context->link->getModuleLink($this->name, 'invoice', [], true),
            ];
        }

        $this->smarty->assign('methods', $methods);
        $this->smarty->assign('testmode', Configuration::get('WASAKREDIT_TEST'));
        $this->smarty->assign('logotype', $this->_path . '/logo.png');

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    public function hookDisplayProductButtons($params)
    {
        if (!Configuration::get('WASAKREDIT_LEASING_ENABLED')) {
            return false;
        }

        if (empty($params['product']->price)) {
            return false;
        }

        $response = $this->_client->get_monthly_cost_widget($params['product']->price);

        if ($response->statusCode != '200') {
            return false;
        }

        $this->smarty->assign('widget', $response->data);

        return $this->display(__FILE__, 'views/templates/hook/displayProductPriceBlock.tpl');
    }

    public function hookPaymentReturn($params)
    {
        if (!Configuration::get('WASAKREDIT_LEASING_ENABLED') || !Configuration::get('WASAKREDIT_INVOICE_ENABLED')) {
            return false;
        }

        $order = $params['objOrder'];
        $state = $order->getCurrentState();

        if (in_array($state, [Configuration::get('PS_OS_PREPARATION')])) {
            $currency = new Currency($order->id_currency);
            $total_to_pay = Tools::displayPrice($order->getOrdersTotalPaid(), $currency, false);

            $this->smarty->assign([
                'total_to_pay' => $total_to_pay,
                'shop_name'    => $this->context->shop->name,
                'checkName'    => $this->checkName,
                'checkAddress' => Tools::nl2br($this->address),
                'status'       => 'ok',
                'id_order'     => $order->id
            ]);

            if (!empty($order->reference)) {
                $this->smarty->assign('reference', $order->reference);
            }
        } else {
            $this->smarty->assign('status', 'failed');
        }

        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Client ID'),
                        'name' => 'WASAKREDIT_CLIENTID',
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Client secret key'),
                        'name' => 'WASAKREDIT_CLIENTSECRET',
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Test Client ID'),
                        'name' => 'WASAKREDIT_TEST_CLIENTID',
                        'required' => false
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Test Client secret key'),
                        'name' => 'WASAKREDIT_TEST_CLIENTSECRET',
                        'required' => false
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Test mode'),
                        'name' => 'WASAKREDIT_TEST',
                        'values' => [
                            [
                                'id' => 'WASAKREDIT_TEST_on',
                                'value' => 1
                            ],
                            [
                                'id' => 'WASAKREDIT_TEST_off',
                                'value' => 0
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Aktivera leasing'),
                        'name' => 'WASAKREDIT_LEASING_ENABLED',
                        'values' => [
                            [
                                'id' => 'WASAKREDIT_LEASING_ENABLED_on',
                                'value' => 1
                            ],
                            [
                                'id' => 'WASAKREDIT_LEASING_ENABLED_off',
                                'value' => 0
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Aktivera faktura'),
                        'name' => 'WASAKREDIT_INVOICE_ENABLED',
                        'values' => [
                            [
                                'id' => 'WASAKREDIT_INVOICE_ENABLED_on',
                                'value' => 1
                            ],
                            [
                                'id' => 'WASAKREDIT_INVOICE_ENABLED_off',
                                'value' => 0
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitWasakreditModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigValues()
    {
        return [
            'WASAKREDIT_CLIENTID' => Tools::getValue(
                'WASAKREDIT_CLIENTID',
                Configuration::get('WASAKREDIT_CLIENTID')
            ),
            'WASAKREDIT_TEST' => Tools::getValue(
                'WASAKREDIT_TEST',
                Configuration::get('WASAKREDIT_TEST')
            ),
            'WASAKREDIT_CLIENTSECRET' => Tools::getValue(
                'WASAKREDIT_CLIENTSECRET',
                Configuration::get('WASAKREDIT_CLIENTSECRET')
            ),
            'WASAKREDIT_TEST_CLIENTID' => Tools::getValue(
                'WASAKREDIT_TEST_CLIENTID',
                Configuration::get('WASAKREDIT_TEST_CLIENTID')
            ),
            'WASAKREDIT_TEST_CLIENTSECRET' => Tools::getValue(
                'WASAKREDIT_TEST_CLIENTSECRET',
                Configuration::get('WASAKREDIT_TEST_CLIENTSECRET')
            ),
            'WASAKREDIT_LEASING_ENABLED' => Tools::getValue(
                'WASAKREDIT_LEASING_ENABLED',
                Configuration::get('WASAKREDIT_LEASING_ENABLED')
            ),
            'WASAKREDIT_INVOICE_ENABLED' => Tools::getValue(
                'WASAKREDIT_INVOICE_ENABLED',
                Configuration::get('WASAKREDIT_INVOICE_ENABLED')
            ),
        ];
    }
}
