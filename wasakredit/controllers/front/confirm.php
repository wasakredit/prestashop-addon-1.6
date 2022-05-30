<?php
/**
 * @author    Wasa Kredit AB
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
 */

class WasakreditConfirmModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
    }

    public function postProcess()
    {
        if (!$cart = $this->context->cart) {
            $this->responseWithError('Failed to load cart');
        }

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->responseWithError('Failed to validate cart content');
        }

        if (!$post = $this->collectData()) {
            $this->responseWithError('Failed to collect post data');
        }

        if (empty($post['data'])) {
            $this->responseWithError('Failed to parse post data');
        }

        foreach ($post['data'] as $attribute) {
            if ($attribute['key'] === 'wasakredit-order-id') {
                $wasa_kredit_id = $attribute['value'];
            } elseif ($attribute['key'] === 'partner_order_number') {
                $partner_order_number = $attribute['value'];
            } elseif ($attribute['key'] === 'partner_cart_number') {
                $partner_cart_number = $attribute['value'];
            } elseif ($attribute['key'] === 'partner_secure_key') {
                $partner_secure_key = $attribute['value'];
            }
        }

        if (empty($wasa_kredit_id) || empty($partner_cart_number) || empty($partner_secure_key)) {
            $this->responseWithError('Failed to validate post data');
        }

        if ($partner_cart_number != $cart->id) {
            $this->responseWithError('Failed to validate cart ids');
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $this->responseWithError('Failed to load customer');
        }

        $status = !empty($wasa_kredit_id)
            ? Configuration::get('PS_OS_PREPARATION')
            : Configuration::get('PS_OS_ERROR');

        $cart_id = (int)$cart->id;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $module = $this->module->displayName;
        $currency_id = (int)$this->context->currency->id;
        $secure_key = $customer->secure_key;

        try {
            $this->module->validateOrder($cart_id, $status, $total, $module, $wasa_kredit_id, [], $currency_id, false, $secure_key);
        } catch (Exception $e) {
            $this->responseWithError($e->getMessage());
        }

        $this->responseWithSuccess();
    }

    private function responseWithSuccess()
    {
        header('HTTP/1.0 200 OK');
        header('Content-Type: application/json');

        die(json_encode([
            'success' => true,
            'message' => 'OK',
        ]));
    }

    private function responseWithError($message = null)
    {
        header('HTTP/1.0 200 OK');
        header('Content-Type: application/json');

        die(json_encode([
            'success'  => false,
            'message'  => $message,
            'redirect' => '/index.php?controller=order&step=1',
        ]));
    }

    private function collectData()
    {
        return is_array($_POST)
            ? $_POST
            : Tools::file_get_contents('php://input');
    }
}
