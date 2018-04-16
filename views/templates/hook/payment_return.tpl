{if $status == 'ok'}
<p>{l s='Su orden en %s esta completa.' sprintf=$shop_name mod='kushkipayment'}
		<br /><br />
		{l s='A continuacion el detalle:' mod='kushkipayment'}
		<br /><br />- {l s='Total:' mod='kushkipayment'} <span class="price"><strong>{$total_to_pay}</strong></span>
		<br /><br />- {l s='Orden:' mod='kushkipayment'}  <strong>{$id_order}</strong>
		<br /><br />- {l s='Ticket:' mod='kushkipayment'}  <strong>{$kushki_ticket}</strong>
		<br /><br /> <strong>{l s='Tu orden será enviada pronto.' mod='kushkipayment'}</strong>
		<br /><br />{l s='Si tienes alguna pregunta por favor contacta a nuestro ' mod='kushkipayment'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='equipo' mod='kushkipayment'}</a>.
	</p>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='kushkipayment'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='kushkipayment'}</a>.
	</p>
{/if}
