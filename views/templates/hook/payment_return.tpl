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
{if $status == 'ok'}
	<p class="alert alert-success">{l s='Your order on %s is complete.' sprintf=$shop_name mod='jnwasakredit'}</p>

	<div class="box">
		<p> 
			{l s='Thank you for using Wasa Kredit. Please check your email. We will contact you soon with more information.' mod='jnwasakredit'}
		</p>
	</div>
{/if}