<?php
/**
 * @author    Jarda Nalezny <jaroslav@nalezny.cz>
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
 *
 */

require(dirname(__FILE__).'./../../../config/config.inc.php');

$id_cart = Tools::getValue('id_cart');
$id_wasakredit = Tools::getValue('id_wasakredit');
$order_reference_id = Tools::getValue('order_reference_id');
$customer_secure_key = Context::getContext()->customer->secure_key;

if (!Tools::isSubmit('customer_secure_key') ||
    Tools::getValue('customer_secure_key') != $customer_secure_key
) {
    die(json_encode(array()));
}

if ((!empty($id_wasakredit)) && (!empty($id_cart)) && (!empty($order_reference_id))) {
    $sql = "SELECT * FROM `"._DB_PREFIX_."cart` 
    WHERE `id_cart` = '".$id_cart."' 
    AND `secure_key` = '".$order_reference_id."'";

    if (!empty(Db::getInstance()->getRow($sql))) {
        echo updateWasaId($id_cart, $id_wasakredit);
    }
}

function updateWasaId($id_cart, $id_wasakredit)
{
    $sql = "SELECT * FROM `"._DB_PREFIX_."jn_wasakredit`
    WHERE `id_cart` = '".$id_cart."'";

    if (empty(Db::getInstance()->getRow($sql))) {
        $sql = 'INSERT INTO '._DB_PREFIX_.'jn_wasakredit
        (id_cart,id_wasakredit) 
        VALUES ("'.$id_cart.'","'.pSQL($id_wasakredit).'")';
    } else {
        $sql = 'UPDATE  '._DB_PREFIX_.'jn_wasakredit
        SET id_wasakredit = "'.$id_wasakredit.'"
        WHERE  id_cart = "'.$id_cart.'"';
    }

    if (Db::getInstance()->execute($sql)) {
        return true;
    } else {
        return false;
    }
}
