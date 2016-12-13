{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}"
       title="{l s='Go back to the Checkout' mod='kushkipayment'}">{l s='Checkout' mod='kushkipayment'}</a>
    <span class="navigation-pipe">{$navigationPipe}</span>{$kushki_title}
{/capture}

<h2>{$kushki_title}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="warning">{l s='Your shopping cart is empty.' mod='kushkipayment'}</p>
{else}
    <form id="kushki-pay-form" action="{$link->getModuleLink('kushkipayment', 'validation', [], true)|escape:'html'}"
          method="post">

    </form>
    <script src="{$kushki_environment}" charset="utf-8"></script>
    <script type="text/javascript">
        var kushki = new KushkiCheckout({
            form: 'kushki-pay-form',
            merchant_id: '{$kushki_public_id}',
            amount: '{$total}',
            is_subscription: false
        });
    </script>
{/if}
