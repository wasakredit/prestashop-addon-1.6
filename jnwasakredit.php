<?php
/**
 * 2008 - 2017 Wasa Kredit B2B
 *
 * MODULE Wasa Kredit
 *
 * @author    Jarda Nalezny <jaroslav@nalezny.cz>
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
 * @version   1.0.0
 * @link      http://www.wasakredit.se
 *
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class JnWasaKredit extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'jnwasakredit';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.3';
        $this->author = 'Wasa Kredit AB';
        $this->need_instance = 0;

        $this->bootstrap = true;
        $this->module_key = 'cbeeaf12d953737cdfc75636d737286a';
        
        parent::__construct();

        $this->displayName = $this->l('Wasa Kredit B2B');
        $this->description = $this->l('The Wasa Kredit B2B checkout.');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99');
    }

    public function install()
    {
        Configuration::updateValue('JN_WASAKREDIT_TEST', false);
        Configuration::updateValue('JN_WASAKREDIT_CLIENTID', '');
        Configuration::updateValue('JN_WASAKREDIT_CLIENTSECRET', '');



        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('payment') &&
            $this->createTable() &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayProductButtons') &&
            $this->registerHook('actionPaymentConfirmation') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayPaymentReturn');
    }


    protected function createTable()
    {

        $res = (bool)Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'jn_wasakredit` (
                `id_cart` int(10) NOT NULL,
                `id_wasakredit` varchar(36) NOT NULL
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8');

        return $res;
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitJn_wasakreditModule')) == true) {
            $this->postProcess();
        }
        return $this->renderForm();
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
        $helper->submit_action = 'submitJn_wasakreditModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Test mode'),
                        'name' => 'JN_WASAKREDIT_TEST',
                        'is_bool' => true,
                        'desc' => $this->l('Disable test mode to go live'),
                        'values' => array(
                            array(
                                'id' => 'JN_WASAKREDIT_TEST_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'JN_WASAKREDIT_TEST_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'name' => 'JN_WASAKREDIT_CLIENTID',
                        'label' => $this->l('Client ID'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-unlock"></i>',
                        'name' => 'JN_WASAKREDIT_CLIENTSECRET',
                        'label' => $this->l('Client secret key'),
                    ),

                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }


    public function hookDisplayProductButtons($params)
    {

        $this->smarty->assign('product_price', $params['product']->price);
        return $this->display(__FILE__, 'views/templates/hook/displayProductPriceBlock.tpl');
    }

    protected function getConfigFormValues()
    {
        return array(
            'JN_WASAKREDIT_CLIENTID' => Tools::getValue(
                'JN_WASAKREDIT_CLIENTID',
                Configuration::get('JN_WASAKREDIT_CLIENTID')
            ),
            'JN_WASAKREDIT_TEST' => Tools::getValue(
                'JN_WASAKREDIT_TEST',
                Configuration::get('JN_WASAKREDIT_TEST')
            ),
            'JN_WASAKREDIT_CLIENTSECRET' => Tools::getValue(
                'JN_WASAKREDIT_CLIENTSECRET',
                Configuration::get('JN_WASAKREDIT_CLIENTSECRET')
            ),
        );
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookPayment($params)
    {

        $this->smarty->assign('module_dir', $this->_path);
        $this->smarty->assign('id_customer', $this->context->cart->id_customer);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $state = $params['objOrder']->getCurrentState();
        if (in_array($state, array(Configuration::get('PS_OS_PREPARATION')))) {
            $this->smarty->assign(array(
                'total_to_pay' => Tools::displayPrice(
                    $params['objOrder']->getOrdersTotalPaid(),
                    new Currency($params['objOrder']->id_currency),
                    false
                ),
                'shop_name' => $this->context->shop->name,
                'checkName' => $this->checkName,
                'checkAddress' => Tools::nl2br($this->address),
                'status' => 'ok',
                'id_order' => $params['objOrder']->id
            ));
            if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
                $this->smarty->assign('reference', $params['objOrder']->reference);
            }
        } else {
            $this->smarty->assign('status', 'failed');
        }
        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/jn_wasakredit.css');
        $this->context->controller->addJS($this->_path.'views/js/jn_wasaajax.js');

        $link = new Link();
        $this->smarty->assign(array(
            'ajax' => Tools::getShopDomainSsl(true).'/modules/jnwasakredit/ajax/'
        ));
        
        return $this->display(__FILE__, 'views/templates/hook/header_productlistajax.tpl');
    }
}
