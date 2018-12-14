{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<script src="https://cdn-uat.kushkipagos.com/kushki-checkout.js"></script>


<section >
    <form id="kushki-pay-form" action="{$action}" method="get"   >
        <input type="hidden" name="cart_id"  value="{$params["cookie"]->id_cart}">
        <input type="hidden" name="total_wt" value="{$total_wt}">
        <input type="hidden" name="total_wout" value="{$total_wout}">
        <input type="hidden" name="currency" value="{$currency_order->iso_code}">
        <input type="hidden" name="language" value="{$language.iso_code}">
        <input type="hidden" name="shipping_order" value="{$shipping_order}">
        <input type="hidden" name="currency_det" value="PESO">
    </form>
    
</section>

<script type="text/javascript">
    var kushki = new KushkiCheckout({
        form: "kushki-pay-form",
        merchant_id: "{$public_key}",
        is_subscription: false,
        {if $ambiente_kushki==1 }
        inTestEnvironment: true,
        {else}
        inTestEnvironment: false,
        {/if}
        {if isset($transfer_COP) and $transfer_COP==true }
        amount: {
            subtotalIva: {$total_wout },
            subtotalIva0: 0,
            iva: {$total_wt - $total_wout }
            },
            callback_url: '{$pse_url}',
            payment_methods:["transfer","credit-card"] ,// optional default credit-card
        {else}
        amount: "{$total_wt}",
        {/if}
        currency: "{$currency_order->iso_code}", // Currency code, by default "USD"
    });
</script>
<script type="text/javascript">

    $( "#pse" ).click(function() {
        alert( "Handler for .click() called." );
    });
</script>
