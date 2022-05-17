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


$product_ids = Tools::getValue('product_ids');


if (count($product_ids) > 0) {
    $products = array();

    foreach ($product_ids as $product_id) {
        if ($product_id > 0) {
            $price = Product::getPriceStatic($product_id, false, null, 6, null, false, true, 1, false, null, 10000);

            $to_array = array(
                'financed_price' => array(
                    'amount' => $price,
                    'currency' => 'SEK'
                ),
                'product_id' => $product_id
            );
            $products[] = $to_array;
        }
    }

    $payload = array(
        'items' => $products
    );

    $response = $client->calculate_leasing_cost($payload);

    echo json_encode($response->data['leasing_costs']);
} else {
    echo '';
}
