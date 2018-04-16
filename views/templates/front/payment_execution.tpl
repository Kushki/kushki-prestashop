{extends file='page.tpl'}

{include file="$tpl_dir./order-steps.tpl"}
{include file="$tpl_dir./errors.tpl"}
{block name="content"}
    <form id="kushki-pay-form" action="{$link->getModuleLink('kushkipayment', 'validation', [], true)|escape:'html'}"
          method="post">

    </form>
    <script src="{$kushki_environment}" charset="utf-8"></script>
    <script type="text/javascript">
        var kushki = new KushkiCheckout({
            form: 'kushki-pay-form',		
            merchant_id: '{$kushki_public_id}',
            amount: '{$total}',
			currency: '{$currency}',						
            is_subscription: false
        });
    </script>
{/block}
