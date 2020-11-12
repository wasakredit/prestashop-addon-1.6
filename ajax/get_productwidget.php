<?php
/**
 * @author    Jarda Nalezny <jaroslav@nalezny.cz>
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
 *
 */

require(dirname(__FILE__).'./../../../config/config.inc.php');

use Sdk\AccessToken;
use Sdk\Api;
use Sdk\Client;
use Sdk\Response;

require _PS_MODULE_DIR_.'jnwasakredit/wasa/Wasa.php';

$config = Configuration::getMultiple(array(
    'JN_WASAKREDIT_CLIENTID',
    'JN_WASAKREDIT_CLIENTSECRET',
    'JN_WASAKREDIT_TEST'
));

$client = new Client(
    $config['JN_WASAKREDIT_CLIENTID'],
    $config['JN_WASAKREDIT_CLIENTSECRET'],
    $config['JN_WASAKREDIT_TEST']
);

$product_price = Tools::getValue('product_price');

if ($product_price > 0) {
    $product_info = array(
        'financial_product' => 'leasing',
        'price_ex_vat' => array(
            'amount' => $product_price,
            'currency' => 'SEK'
        )
    );

    $response = $client->create_product_widget($product_info);
    echo json_encode($response->data);
} else {
    echo '';
}
