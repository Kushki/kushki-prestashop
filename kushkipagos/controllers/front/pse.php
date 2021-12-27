<?php
/**
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
 */
include_once _PS_MODULE_DIR_.'kushkipagos/classes/kushki/autoload.php';

use kushki\lib\Transaction;

class KushkipagosPseModuleFrontController extends ModuleFrontController
{
    const TEST_URL = 'https://api-uat.kushkipagos.com';
    const PROD_URL = 'https://api.kushkipagos.com';

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $module_name = $this->module->displayName;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $currency = $this->context->currency;
        $customer = new Customer($cart->id_customer);
        $token_response = Tools::getValue('token');


        // realizamos consulta de base de datos para el token
        $query = 'SELECT * FROM ' . _DB_PREFIX_ . 'kushkipagos WHERE `token` = \'' . $token_response . '\' limit 1 ';
        $_async_array = Db::getInstance()->executeS($query);

        //entorno de la consulta

        if ($_async_array[0]['message'] == 'card_async') {
            $message = 'Transaction card_async';

            $url_check = $this->callUrl() . '/card-async/v1/status/' . $token_response;

            $data["private-merchant-id"] = Configuration::get('KUSHKIPAGOS_PRIVATE_KEY');

            $responseRaw = \Httpful\Request::get($url_check)
                ->sendsJson()
                ->withStrictSSL()
                ->addHeaders(array(
                    'private-merchant-id' => $data["private-merchant-id"],
                ))
                ->send();

            $transaction_result = new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);

            if ($transaction_result->isSuccessful()) {

                $cookie2 = new Cookie('card_async_ticketNumber'); //make your own cookie
                $cookie2->setExpire(time() + 120 * 60); // 2 minutes for example
                $cookie2->variable_name = $responseRaw->body->ticketNumber;
                $cookie2->write();
                $cookie3 = new Cookie('card_async_status'); //make your own cookie
                $cookie3->setExpire(time() + 120 * 60); // 2 minutes for example
                $cookie3->variable_name = 'requestedToken';
                $cookie3->write();
                $cookie4 = new Cookie('card_async_transactionReference'); //make your own cookie
                $cookie4->setExpire(time() + 120 * 60); // 2 minutes for example
                $cookie4->variable_name = $responseRaw->body->transactionReference;
                $cookie4->write();

                // variables globales de la consulta para pendiente
                $_cookieBody = new Cookie('_body_main_card_async');
                $_cookieBody->setExpire(time() + 120 * 60);
                $_cookieBody->variable_name =$responseRaw->body->created/1000;
                $_cookieBody->write();

                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $_async_array[0]['cart_id'] . '&id_module=' . (int)$this->module->id . '&id_order=' . $_async_array[0]['order_id'] . '&key=' . $customer->secure_key);


            } else {
                $payment_status = Configuration::get('PS_OS_ERROR');

                /**
                 * Generamos la validación de la orden del carrito
                 */
                $this->module->validateOrder((int)$cart->id, $payment_status, $total, $module_name, $message, array(), (int)$currency->id, false, $customer->secure_key);

                /**
                 * Cargamos la pantalla de confirmación del pedido
                 */
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);

            }

        } else {
            $message = 'Transfer Transaction';
            // url de la cosulta
            $url_check = $this->callUrl() . '/transfer/v1/status/' . $token_response;

            /**
             * Defiinimos el private-id
             */
            $data["private-merchant-id"] = Configuration::get('KUSHKIPAGOS_PRIVATE_KEY');

            /**
             * Reaizamos el llamado http
             */
            $responseRaw = \Httpful\Request::get($url_check)
                ->sendsJson()
                ->withStrictSSL()
                ->addHeaders(array(
                    'private-merchant-id' => $data["private-merchant-id"],
                ))
                ->send();

            $transaccion_result = new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);

            if ($transaccion_result->isSuccessful()) {

                $cookie2 = new Cookie('transfer_ticketNumber'); //make your own cookie
                $cookie2->setExpire(time() + 120 * 60); // 2 minutes for example
                $cookie2->variable_name = $responseRaw->body->ticketNumber;
                $cookie2->write();

                $cookie3 = new Cookie('transfer_status'); //make your own cookie
                $cookie3->setExpire(time() + 120 * 60); // 2 minutes for example
                $cookie3->variable_name = 'initializedTransaction';
                $cookie3->write();

                $cookie4 = new Cookie('transfer_trazabilityCode'); //make your own cookie
                $cookie4->setExpire(time() + 120 * 60); // 2 minutes for example
                $cookie4->variable_name = $responseRaw->body->trazabilityCode;
                $cookie4->write();

                $payment_status = Configuration::get('PS_OS_BANKWIRE');
                $cookie5 = new Cookie('transfer_statusDetail'); //make your own cookie
                $cookie5->setExpire(time() + 120 * 60); // 2 minutes for example
                $cookie5->variable_name = $payment_status;
                $cookie5->write();

                // variables globales de la consulta para pendiente
                $_cookieBody = new Cookie('_body_main');
                $_cookieBody->setExpire(time() + 120 * 60);
                $_cookieBody->variable_name = serialize($responseRaw->body);
                $_cookieBody->write();

                // bank name
                $url_bank = $this->callUrl() . "/transfer/v1/bankList/";
                $responseRawBank = \Httpful\Request::get($url_bank)
                    ->sendsJson()
                    ->withStrictSSL()
                    ->addHeaders(array(
                        'Public-Merchant-Id' => Configuration::get('KUSHKIPAGOS_PUBLIC_KEY')
                    ))
                    ->send();

                foreach ($responseRawBank->body as $bank) {
                    if ($responseRaw->body->bankId == $bank->code) {

                        $_cookieBank = new Cookie('_bankName');
                        $_cookieBank->setExpire(time() + 120 * 60);
                        $_cookieBank->variable_name = $bank->name;
                        $_cookieBank->write();
                    }
                }

                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $_async_array[0]['cart_id'] . '&id_module=' . (int)$this->module->id . '&id_order=' . $_async_array[0]['order_id'] . '&key=' . $customer->secure_key);

            } else {
                $payment_status = Configuration::get('PS_OS_ERROR');

                /**
                 * Generamos la validación de la orden del carrito
                 */
                $this->module->validateOrder((int)$cart->id, $payment_status, $total, $module_name, $message, array(), (int)$currency->id, false, $customer->secure_key);

                /**
                 * Cargamos la pantalla de confirmación del pedido
                 */
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);

            }
        }

    }

    private function callUrl()
    {
        $env = Configuration::get('KUSHKIPAGOS_DEV');// entorno de desarrollo del módulo

        return !$env ? self::PROD_URL : self::TEST_URL;
    }
}