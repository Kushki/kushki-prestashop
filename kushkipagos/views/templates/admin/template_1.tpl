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

<div class="panel">
	<div class="row kushkipagos-header">
		<img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo-ligth.png" class="col-xs-6 col-md-4 text-center" id="payment-logo" />
		<div class="col-xs-6 col-md-4 text-center">
			<h2 style="color: rgb(56,175,139)">Acepta Pagos Fácilmente</h2>
			<h4>con la pasarela de pagos más fácil y ágil de conectar</h4>
		</div>
		<div class="col-xs-12 col-md-4 text-center">
            <a href="https://www.kushkipagos.com/aplicacion" target="_blank"  class="btn btn-success" id="create-account-btn">Aplica para una cuenta</a><br />
			{l s='Already have an account?' mod='kushkipagos'}<a href="https://backoffice.kushkipagos.com/login" > {l s='Log in' mod='kushkipagos'}</a>
		</div>
	</div>

	<hr />
	
	<div class="kushkipagos-content">
		<div class="row">
			{*<div class="col-md-6">*}
				{*<h5>{l s='My payment module offers the following benefits' mod='kushkipagos'}</h5>*}
				{*<dl>*}
					{*<dt>&middot; {l s='Increase customer payment options' mod='kushkipagos'}</dt>*}
					{*<dd>{l s='Visa®, Mastercard®, Diners Club®, American Express®, Discover®, Network and CJB®, plus debit, gift cards and more.' mod='kushkipagos'}</dd>*}
					{**}
					{*<dt>&middot; {l s='Help to improve cash flow' mod='kushkipagos'}</dt>*}
					{*<dd>{l s='Receive funds quickly from the bank of your choice.' mod='kushkipagos'}</dd>*}
					{**}
					{*<dt>&middot; {l s='Enhanced security' mod='kushkipagos'}</dt>*}
					{*<dd>{l s='Multiple firewalls, encryption protocols and fraud protection.' mod='kushkipagos'}</dd>*}
					{**}
					{*<dt>&middot; {l s='One-source solution' mod='kushkipagos'}</dt>*}
					{*<dd>{l s='Conveniance of one invoice, one set of reports and one 24/7 customer service contact.' mod='kushkipagos'}</dd>*}
				{*</dl>*}
			{*</div>*}
			
			{*<div class="col-md-6">*}
				{*<h5>{l s='FREE My Payment Module Glocal Gateway (Value of 400$)' mod='kushkipagos'}</h5>*}
				{*<ul>*}
					{*<li>{l s='Simple, secure and reliable solution to process online payments' mod='kushkipagos'}</li>*}
					{*<li>{l s='Virtual terminal' mod='kushkipagos'}</li>*}
					{*<li>{l s='Reccuring billing' mod='kushkipagos'}</li>*}
					{*<li>{l s='24/7/365 customer support' mod='kushkipagos'}</li>*}
					{*<li>{l s='Ability to perform full or patial refunds' mod='kushkipagos'}</li>*}
				{*</ul>*}
				<br />
				<em class="text-muted small">
					* Solo se aceptan tarjetas de débito locales de emisores que lo autoricen.
				</em>
			{*</div>*}
		</div>

		<hr />
		
		<div class="row">
			<div class="col-md-12">
				<h4>Acepta pagos usando tarjetas de crédito, débito y transferencia</h4>

				<div class="row">
					{*<img src="{$module_dir|escape:'html':'UTF-8'}views/img/template_1_cards.png" class="col-md-6" id="payment-logo" />*}
					<div class="col-md-6">
						<h6 class="text-branded">Para transacciones USD, COP y MXN </h6>
						{*<p class="text-branded">{l s='Call 888-888-1234 if you have any questions or need more information!' mod='kushkipagos'}</p>*}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="panel">
    <h4>Url para actualizar estados de transacciónes PSE</h4>
    <strong>{$web_url}</strong>
</div>



