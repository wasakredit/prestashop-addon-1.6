{*
 * 2008 - 2017 Wasa Kredit B2B
 *
 * MODULE Wasa Kredit
 *
 * @version   1.0.0
 * @author    Jarda Nalezny <jaroslav@nalezny.cz>
 * @link      http://www.presto-changeo.com
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
 *
*}

<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module" id="jn_wasakredit_payment_button">
			<a href="{$link->getModuleLink('jnwasakredit', 'payment', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='WASA KREDIT LEASING' mod='jnwasakredit'}">
				<img src="{$module_dir|escape:'htmlall':'UTF-8'}/logo.png" alt="{l s='WASA KREDIT LEASING' mod='jnwasakredit'}" width="32" height="32" />
				{l s='WASA KREDIT LEASING' mod='jnwasakredit'}
			</a>
		</p>
	</div>
</div>

