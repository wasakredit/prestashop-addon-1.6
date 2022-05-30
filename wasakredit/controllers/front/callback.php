<?php
/**
 * @author    Wasa Kredit AB
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
*/

use Sdk\AccessToken;
use Sdk\Api;
use Sdk\ClientFactory;
use Sdk\Client;
use Sdk\Response;

require _PS_MODULE_DIR_.'wasakredit/vendor/wasa/client-php-sdk/Wasa.php';
require_once _PS_MODULE_DIR_.'wasakredit/utility/SdkHelper.php';

class WasakreditCallbackModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
    }

    public function postProcess()
    {
        if (!$post = $this->collectData()) {
            $this->responseWithError('Failed to parse POST request.');
        }

        if (empty($post['data'])) {
            $this->responseWithError('Failed to parse POST request.');
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

        if (empty($wasa_kredit_id)) {
            $this->responseWithError('Failed to extract id.');
        }

        if (empty($partner_cart_number)) {
            $this->responseWithError('Failed to extract cart id.');
        }

        if (!$order_id = Order::getOrderByCartId($partner_cart_number)) {
            $this->responseWithError('Failed to load order.');
        }

        $response = $this->_client->add_order_reference($wasa_kredit_id, [
            'key'   => 'partner_order_number',
            'value' => $order_id,
        ]);

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
        header('HTTP/1.0 400 Bad Request');
        header('Content-Type: application/json');

        die(json_encode([
            'error'   => true,
            'message' => $message,
        ]));
    }

    private function collectData()
    {
        return is_array($_POST)
            ? $_POST
            : Tools::file_get_contents('php://input');
    }
}
