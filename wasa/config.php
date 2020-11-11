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

function wasa_config($key = '')
{
    $wasa_configuration = array(
        'base_url' => 'https://b2b.services.wasakredit.se',
        'access_token_url' => 'https://b2b.services.wasakredit.se/auth/connect/token'
    );

    return isset($wasa_configuration[$key]) ? $wasa_configuration[$key] : null;
}
