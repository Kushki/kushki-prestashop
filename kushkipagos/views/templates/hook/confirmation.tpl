{*
* 2007-2018 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}



{if (isset($status) == true) && ($status == 'ok')}

	{if (isset($is_card_async) == true) and ($is_card_async == 1 )}
		<h3 style="color: darkred" >El pago en {$shop_name} mediante kushki se encuentra pendiente, una vez confirmado el pago será notificado por correo electrónico,</h3>
		<p>
			{if (isset($card_async_created))}
				<br />- Fecha de transacción : <span class="reference"><strong>{$card_async_created|escape:'html':'UTF-8'}</strong></span>
			{/if}
			{if (isset($card_async_ticketNumber))}
				<br />- Número de ticket : <span class="reference"><strong>{$card_async_ticketNumber|escape:'html':'UTF-8'}</strong></span>
			{/if}
			{if (isset($card_async_transactionReference))}
				<br />- Transaction reference No. : <span class="reference"><strong>{$card_async_transactionReference|escape:'html':'UTF-8'}</strong></span>
			{/if}

			<br />- Valor total : <span class="price"><strong>{$total_to_pay|escape:'htmlall':'UTF-8'}</strong></span>
			<br />- Referencia : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>

			<br /><br />Le hemos enviado un correo electrónico con esta información.
			<br /><br />Para cualquier pregunta o para más información, contacte con nuestro <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">departamento de atención al cliente.</a>
		</p>
	{else}
		{if (isset($is_transfer) == true) and ($is_transfer == 1 )}

			{if (isset($cookie_transfer_status)==true) && ($cookie_transfer_status == 'approvedTransaction' )}
				<h3>El pago en {$shop_name} mediante kushki

					se a realizado exitosamente y a sido aceptado,

				</h3>
				<p>
					<br />- Valor del pago : <span class="price"><strong>{$total_to_pay|escape:'htmlall':'UTF-8'}</strong></span>
					<br />- Referencia : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>
					{if (isset($ticketNumber))}
						<br />- Número de ticket : <span class="reference"><strong>{$ticketNumber|escape:'html':'UTF-8'}</strong></span>
					{/if}
					{if (isset($transfer_trazabilityCode))}
						<br />- CUS : <span class="reference"><strong>{$transfer_trazabilityCode|escape:'html':'UTF-8'}</strong></span>
					{/if}
					<br /><br />Le hemos enviado un correo electrónico con esta información.
					<br /><br />Para cualquier pregunta o para más información, contacte con nuestro <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">departamento de atención al cliente.</a>
				</p>
			{elseif (isset($cookie_transfer_status)==true) && ($cookie_transfer_status == 'initializedTransaction' ) }
				<h3 style="color: darkred" >El pago en {$shop_name} mediante kushki

					se encuentra pendiente, una vez confirmado el pago será notificado por correo electrónico,

				</h3>
				<p>
					{if (isset($var_transfer_status))}
						<br />- Estado : <span class="reference"><strong style="color: rgb(228,158,86)">{$var_transfer_status|escape:'html':'UTF-8'}</strong></span>
					{/if}
					<br />- Por favor contactarse : <span class="reference"><strong>+57 3208068759</strong></span>
					{if (isset($var_transfer_documentNumber))}
						<br />- NIT : <span class="reference"><strong>{$var_transfer_documentNumber|escape:'html':'UTF-8'}</strong></span>
					{/if}
					{if (isset($var_transfer_razon_social))}
						<br />- Razon Social : <span class="reference"><strong>{$var_transfer_razon_social|escape:'html':'UTF-8'}</strong></span>
					{/if}
					{if (isset($var_transfer_created))}
						<br />- Fecha de transacción : <span class="reference"><strong>{$var_transfer_created|escape:'html':'UTF-8'}</strong></span>
					{/if}
					{if (isset($var_transfer_bankName))}
						<br />- Banco : <span class="reference"><strong>{$var_transfer_bankName|escape:'html':'UTF-8'}</strong></span>
					{/if}
					{if (isset($transfer_trazabilityCode))}
						<br />- CUS : <span class="reference"><strong>{$transfer_trazabilityCode|escape:'html':'UTF-8'}</strong></span>
					{/if}
					{if (isset($var_transfer_paymentDescription))}
						<br />- Descripción : <span class="reference"><strong>{$var_transfer_paymentDescription|escape:'html':'UTF-8'}</strong></span>
					{/if}

					<br />- Valor total : <span class="price"><strong>{$total_to_pay|escape:'htmlall':'UTF-8'}</strong></span>
					<br />- Referencia : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>
					{if (isset($ticketNumber))}
						<br />- Número de ticket : <span class="reference"><strong>{$ticketNumber|escape:'html':'UTF-8'}</strong></span>
					{/if}
					<br /><br />Le hemos enviado un correo electrónico con esta información.
					<br /><br />Para cualquier pregunta o para más información, contacte con nuestro <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">departamento de atención al cliente.</a>
				</p>
			{else}
				<h3 style="color: darkred">El pago en {$shop_name} mediante kushki

					no a sido aceptado,
				</h3>
				<p>
					<br />- Reference  <span class="reference"> <strong>{$reference|escape:'html':'UTF-8'}</strong></span>
					<br /><br />Por favor trate de ordenar otra vez
					<br /><br />Pongase en contacto con servicio al cliente de pse
					<br /><br />Si tiene preguntas, comentarios por favor pongase en contacto con nosotros
					<br /><br />If you have questions, comments or concerns, please contact our <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='kushkipagos'}</a>
				</p>

			{/if}

		{else}
			{if (isset($pdfUrl) == false)}
				<h3 style="color: darkgreen">El pago con tarjeta en {$shop_name} por medio de kushki ha sido confirmado. </h3>
			{/if}
			{if (isset($pdfUrl))}
				<h3 style="color: darkgreen">El pago con Efectivo en {$shop_name} por medio de kushki ha sido confirmado. </h3>
			{/if}
			<p style="color: darkgreen">
				<br />- Valor del pago : <span class="price"><strong>{$total_to_pay|escape:'htmlall':'UTF-8'}</strong></span>
				<br />- Referencia : <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>
				{if (isset($pdfUrl))}
					<br />- Orden de pago : <span class="reference">
				<strong>
				<a href="{$pdfUrl|escape:'html':'UTF-8'}" target="_blank">{$pdfUrl|escape:'html':'UTF-8'}</a>
				</strong></span>
				{/if}


				{if (isset($ticketNumber))}
					<br />- Número de ticket : <span class="reference"><strong>{$ticketNumber|escape:'html':'UTF-8'}</strong></span>
				{/if}
				<br /><br />Le hemos enviado un correo electrónico con esta información.
				<br /><br />Para cualquier pregunta o para más información, contacte con nuestro <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">departamento de atención al cliente.</a>
			</p>

		{/if}
	{/if}


{else}
	<h3>El pago con kushki en {$shop_name} no ha sido aceptado.</h3>
	<p>
		<br />- Reference  <span class="reference"> <strong>{$reference|escape:'html':'UTF-8'}</strong></span>
		<br /><br />Por favor trate de ordenar otra vez.
		<br /><br />Si tiene preguntas, comentarios por favor pongase en contacto con nosotros.
		<br /><br />If you have questions, comments or concerns, please contact our <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='kushkipagos'}</a>
	</p>
{/if}




<hr />