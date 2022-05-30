{*
 * 2008 - 2022 Wasa Kredit B2B
 *
 * MODULE Wasa Kredit
 *
 * @version   1.0.0
 * @author    Wasa Kredit AB
 * @link      http://www.wasakredit.se
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
*}

{foreach $methods as $method}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module">
                <a href="{$method['link']}" class="wasakredit" title="{$method['text']}">
                    {$method['text']} - <small>{$method['extra']}</small>
                    {if $testmode == true}
                        <small style="color:green;">[TESTMODE]</small>
                    {/if}
                </a>
            </p>
        </div>
    </div>
{/foreach }
