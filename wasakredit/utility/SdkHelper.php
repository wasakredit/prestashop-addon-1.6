<?php
require_once _PS_MODULE_DIR_.'wasakredit/vendor/wasa/client-php-sdk/Wasa.php';

use Sdk\ClientFactory;
use Sdk\Client;

class Wasa_Kredit_Checkout_SdkHelper
{
    public static function CreateClient()
    {
        $config = Configuration::getMultiple([
            'WASAKREDIT_CLIENTID',
            'WASAKREDIT_CLIENTSECRET',
            'WASAKREDIT_TEST',
            'WASAKREDIT_TEST_CLIENTID',
            'WASAKREDIT_TEST_CLIENTSECRET'
        ]);

        return Sdk\ClientFactory::CreateClient(
            $config['WASAKREDIT_TEST']
                ? $config['WASAKREDIT_TEST_CLIENTID']
                : $config['WASAKREDIT_CLIENTID'],
            $config['WASAKREDIT_TEST']
                ? $config['WASAKREDIT_TEST_CLIENTSECRET']
                : $config['WASAKREDIT_CLIENTSECRET'],
            $config['WASAKREDIT_TEST']
        );
    }
}
