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

class WasakreditInvoiceModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;

        parent::initContent();
    }

    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'wasakredit') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die(
                $this->module->l('This payment method is not available.', 'wasakredit')
            );
        }

        $this->context->smarty->assign($this->getTemplateVars());

        $this->setTemplate('invoice.tpl');
    }

    public function getTemplateVars()
    {
        $this->_client = Wasa_Kredit_Checkout_SdkHelper::CreateClient();

        $cart = $this->context->cart;

        $customer = new Customer($cart->id_customer);
        $purchaser_email = $customer->email;

        $b_address = new Address($cart->id_address_invoice);
        $d_address = new Address($cart->id_address_delivery);
        $purchaser_name = $b_address->firstname . ' ' . $b_address->lastname;

        $billing_address = [
            'company_name'   => $b_address->company,
            'street_address' => $b_address->address1,
            'postal_code'    => $b_address->postcode,
            'city'           => $b_address->city,
            'country'        => $this->context->country->getNameById($this->context->language->id, $b_address->id_country),
        ];

        $delivery_address = [
            'company_name'   => $d_address->company,
            'street_address' => $d_address->address1,
            'postal_code'    => $d_address->postcode,
            'city'           => $d_address->city,
            'country'        => $this->context->country->getNameById($this->context->language->id, $d_address->id_country),
        ];

        $recipient_name = $d_address->firstname . ' ' . $d_address->lastname;

        $cart_items = [];

        foreach ($cart->getProducts() as $product) {
            $vat = $product['price_with_reduction'] - $product['price_with_reduction_without_tax'];

            $item = [
              'product_id'           => $product['id_product'],
              'product_name'         => $product['name'],
              'quantity'             => $product['cart_quantity'],
              'vat_percentage'       => $product['rate'],
              'price_incl_vat'       => $this->apply_currency($product['price_with_reduction']),
              'price_ex_vat'         => $this->apply_currency($product['price_with_reduction_without_tax']),
              'vat_amount'           => $this->apply_currency($product['price_with_reduction']-$product['price_with_reduction_without_tax']),
              'total_price_incl_vat' => $this->apply_currency($product['price_with_reduction'] * $product['cart_quantity']),
              'total_price_ex_vat'   => $this->apply_currency($product['price_with_reduction_without_tax'] * $product['cart_quantity']),
              'total_vat'            => $this->apply_currency($vat * $product['cart_quantity']),
            ];

            array_push($cart_items, $item);
        }

        $shipping_price_incl_vat = $cart->getTotalShippingCost(null, true);
        $shipping_price_ex_vat = $cart->getTotalShippingCost(null, false);
        $shipping_price_vat = $shipping_price_incl_vat - $shipping_price_ex_vat;

        $shipping_cart_item = [
          'product_id'           => $cart->id_carrier,
          'product_name'         => 'Frakt',
          'quantity'             => 1,
          'vat_percentage'       => $product['rate'],
          'price_incl_vat'       => $this->apply_currency($shipping_price_incl_vat),
          'price_ex_vat'         => $this->apply_currency($shipping_price_ex_vat),
          'vat_amount'           => $this->apply_currency($shipping_price_vat),
          'total_price_incl_vat' => $this->apply_currency($shipping_price_incl_vat),
          'total_price_ex_vat'   => $this->apply_currency($shipping_price_ex_vat),
          'total_vat'            => $this->apply_currency($shipping_price_vat)
        ];

        array_push($cart_items, $shipping_cart_item);

        $purchaser_phone = '';

        if (!empty($b_address->phone_mobile)) {
            $purchaser_phone = $b_address->phone_mobile;
        } elseif (!empty($b_address->phone)) {
            $purchaser_phone = $b_address->phone;
        }

        $recipient_phone = '';

        if (!empty($d_address->phone_mobile)) {
            $recipient_phone = $d_address->phone_mobile;
        } elseif (!empty($d_address->phone)) {
            $recipient_phone = $d_address->phone;
        }

        $total_price_incl_vat = $cart->getOrderTotal(true, Cart::BOTH);
        $total_price_ex_vat = $cart->getOrderTotal(false, Cart::BOTH);
        $total_vat = $total_price_incl_vat - $total_price_ex_vat;

        $confirmation_url = sprintf(
            '%s/index.php?controller=order-confirmation&id_cart=%s&key=%s',
            $this->context->link->getBaseLink(),
            $cart->id,
            $cart->secure_key
        );

        $payload = [
            'order_references' => [
                [
                    'key'   => 'partner_order_number',
                    'value' => null
                ],
                [
                    'key'   => 'partner_cart_number',
                    'value' => $cart->id
                ],
                [
                    'key'   => 'partner_secure_key',
                    'value' => $cart->secure_key
                ],
            ],
            'purchaser_name'               => $purchaser_name,
            'purchaser_email'              => $purchaser_email,
            'customer_organization_number' => $b_address->dni,
            'purchaser_phone'              => $purchaser_phone,
            'recipient_name'               => $recipient_name,
            'recipient_phone'              => $recipient_phone,
            'cart_items'                   => $cart_items,
            'total_price_incl_vat'         => $this->apply_currency($total_price_incl_vat),
            'total_price_ex_vat'           => $this->apply_currency($total_price_ex_vat),
            'total_vat'                    => $this->apply_currency($total_vat),
            'request_domain'               => $this->context->link->getBaseLink(),
            'confirmation_callback_url'    => $confirmation_url,
            'ping_url'                     => $this->context->link->getModuleLink('wasakredit', 'callback', [], true),
        ];

        $response = $this->_client->create_invoice_checkout($payload);

        if (!empty($response->data['invalid_properties'][0]['error_message'])) {
            $response = $response->data['invalid_properties'][0]['error_message'];
            $error = true;
            $wasa_id = 0;
        } else {
            preg_match('/id=([^"]+)/', $response->data, $wasa_id);
            $error = false;
            $wasa_id = $wasa_id[1] ?? null;
        }

        return [
            'response'    => $response,
            'iframe'      => $response->data,
            'error'       => $error,
            'confirm_url' => $this->context->link->getModuleLink('wasakredit', 'confirm', [], true),
        ];
    }

    public function apply_currency($amount)
    {
        return [
            'amount'   => number_format($amount, 2, '.', ''),
            'currency' => $this->context->currency->iso_code
        ];
    }
}
