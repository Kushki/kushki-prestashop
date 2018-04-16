<?php
/*
* 2007-2016 PrestaShop
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
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class KushkiPaymentPaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		$cart = $this->context->cart;
		$current_currency   = new Currency((int)($cart->id_currency));
		Context::getContext()->smarty->assign(array(
			'nbProducts' => $cart->nbProducts(),
			'total' => number_format($cart->getOrderTotal(true, Cart::BOTH),2),
			'currency' => $current_currency->iso_code,			
			'kushki_title'     => Configuration::get( 'KUSHKI_TITLE' ),
			'kushki_environment'    => ( Configuration::get( 'KUSHKI_TEST' ) ) ? 'https://cdn-uat.kushkipagos.com/kushki-checkout.js' : 'https://cdn.kushkipagos.com/kushki-checkout.js',
			'kushki_public_id' => Configuration::get( 'KUSHKI_PUBLIC_KEY' )
		));

		$this->setTemplate('module:kushkipayment/views/templates/front/payment_execution.tpl');
		

	}
}
