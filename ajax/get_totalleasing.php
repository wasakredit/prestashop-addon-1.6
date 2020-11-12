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

$total = Tools::getValue('total');

if ($total > 0) {
    $payload = array(
        'total_amount' => array(
            'amount' => $total,
            'currency' => 'SEK'
        )
    );

    $response = $client->calculate_total_leasing_cost($payload);

    if (!empty($response->data['contract_lengths'])) {
        echo json_encode($response->data['contract_lengths']);
    } else {
        echo '';
    }
} else {
    echo '';
}
