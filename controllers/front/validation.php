<?php
/**
 * @author    Jarda Nalezny <jaroslav@nalezny.cz>
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
 *
 */

class JnWasaKreditValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
    }
    public function postProcess()
    {
        $context = Context::getContext();
        $cart = $this->context->cart;

        $sql = "SELECT * FROM `"._DB_PREFIX_."jn_wasakredit` 
        WHERE `id_cart` = '".$cart->id."'";

        $id_wasakredit = Db::getInstance()->getRow($sql)['id_wasakredit'];

        $this->checkExistingOrder($context->cookie->id_wasakredit);

        if ($cart->id_customer == 0 ||
            empty($id_wasakredit) ||
            $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 ||
            !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'jnwasakredit') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $mailVars =   array();

        try {
            if ($id_wasakredit) {
                $this->module->validateOrder(
                    (int)$cart->id,
                    Configuration::get('PS_OS_PREPARATION'),
                    $total,
                    $this->module->displayName,
                    $id_wasakredit,
                    $mailVars,
                    (int)$currency->id,
                    false,
                    $customer->secure_key
                );
            } else {
                $this->module->validateOrder(
                    (int)$cart->id,
                    Configuration::get('PS_OS_ERROR'),
                    $total,
                    $this->module->displayName,
                    $id_wasakredit,
                    $mailVars,
                    (int)$currency->id,
                    false,
                    $customer->secure_key
                );
            }
        } catch (Exception $e) {
        }

        $id_order = Order::getOrderByCartId($cart->id);

        if ($id_order) {
            Tools::redirect(
                'index.php?controller=order-confirmation&id_cart='.
                (int)$cart->id.'&id_module='.
                (int)$this->module->id.'&id_order='.
                $this->module->currentOrder.'&key='.
                $customer->secure_key
            );
        }
    }

    private function checkExistingOrder($id_wasakredit)
    {
        $sql = "SELECT `id_cart` FROM `"._DB_PREFIX_."jn_wasakredit` 
        WHERE `id_wasakredit` = '".psql($id_wasakredit)."'";

        $id_cart = Db::getInstance()->getValue($sql);
        $cart = new Cart($id_cart);
        $customer = new Customer($cart->id_customer);
        $id_order = Order::getOrderByCartId($id_cart);

        if ($id_order){
            return Tools::redirect(
                'index.php?controller=order-confirmation&id_cart='.
                (int)$id_cart.'&id_module='.
                (int)$this->module->id.'&id_order='.
                $id_order.'&key='.
                $customer->secure_key
            );
        }
    }
}
