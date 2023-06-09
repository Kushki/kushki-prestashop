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
use kushki\lib\PreAuth;

class KushkipagosValidationModuleFrontController extends ModuleFrontController
{
    const PLUGIN_URL = 'https://api.kushkipagos.com/plugins/v1/';
    const TEST_PLUGIN_URL = 'https://api-uat.kushkipagos.com/plugins/v1/';
    const PREAUTH = 'preAuth';
    const CHARGE = 'charge';
    const CAPTURE = 'capture';

    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        /**
         * Set message log.
         */
        $logger = new FileLogger(); //0 == nivel de debug. Sin esto logDebug() no funciona.
        $logger->setFilename(_PS_ROOT_DIR_."/kushkiLogs/".date('Y-m-d').".log");
        $logger->logInfo('------- New kushki option select -------');

        /**
         * Status de la transacción.
         */
        $_internal_status = true;

        /**
         * Get var from query params.
         */
        $cartId = Tools::getValue('cart_id');
        $totalWt = Tools::getValue('total_wt');
        $kushkiToken = Tools::getValue('kushkiToken');
        $kushkiDeferred = (int)Tools::getValue('kushkiDeferred');
        $kushkiDeferredType =  Tools::getValue('kushkiDeferredType');
        $kushkiMonthsOfGrace = Tools::getValue('kushkiMonthsOfGrace');
        $kushkiPaymentMethod = Tools::getValue('kushkiPaymentMethod');
        $kushkiCurrency = Tools::getValue('currency');

        $logger->logInfo("Payment method select: ".$kushkiPaymentMethod);

        /**
         * If the module is not active anymore, no need to process anything.
         */
        if ($this->module->active == false) {
            $logger->logError("Module kushki pagos is not active");
            die;
        }

        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            $logger->logError('Error on cart, redirect ok');
            Tools::redirect('index.php?controller=order&step=1');
        }

        /**
         * Verificamos autorización.
         * Fixme: cambiar ps_checkpayment por kushkipagos.
         * version 2.1.4
         */
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'kushkipagos') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            $err_msg=$this->trans('This payment method is not available.', array(), 'Modules.Checkpayment.Shop');
            $logger->logError($err_msg.' / This payment method is not available');
            Tools::redirect(Context::getContext()->link->getModuleLink('kushkipagos', 'error', array('error_msg' => $err_msg))); // plantilla de error para módulos nativos
            die($this->trans('This payment method is not available.', array(), 'Modules.Checkpayment.Shop'));
        }

        // Obtener datos cliente
        $customer = new Customer($cart->id_customer);

        // Obtener datos de entrega
        $address = new CustomerAddress((int) $cart->id_address_delivery);

        if (!Validate::isLoadedObject($customer)) {
            $logger->logError('Error, no object find, redirect ok');
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $totalCartAmount = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $total = round($totalCartAmount, 2);

        $module_name = $this->module->displayName;

        /**
         * Vemos si es preauth or charge.
         */

        if(empty($kushkiToken)) {
            $error_message = 'Kushki Error 017: Tarjeta no válida';
            $ex_detailed_message = $this->trans('An error occurred while processing payment: ' . $error_message, array(), 'Modules.Checkpayment.Shop');
            Tools::redirect(Context::getContext()->link->getModuleLink('kushkipagos', 'error', array('error_msg' => $ex_detailed_message)));
            return;
        }
        if($kushkiPaymentMethod == "preauth"){
            // Obtener Payload
            $payload = $this->getPayloadPreAuth( $customer, $address, $kushkiCurrency, $cartId, $totalWt, $kushkiToken );

            // Preauth Request
            $transaction_response = $this->initPreAuthRequest( $payload );

        }else{
            $transaction_response = $this->kushki($kushkiCurrency,$cartId,$kushkiToken,$kushkiDeferred, $kushkiPaymentMethod, $address, $kushkiDeferredType, $kushkiMonthsOfGrace );
        }

        /**
         * Comprobamos el estado de la transacción.
         */

        if($_internal_status){
            if (isset($transaction_response) && $transaction_response->isSuccessful()) {

                $payment_status = Configuration::get('PS_OS_PAYMENT'); // status del pago
                $message = 'Transaction '.$kushkiPaymentMethod;

                switch ($kushkiPaymentMethod){
                    case "card_async":
                        $payment_status = Configuration::get('PS_OS_BANKWIRE');

                        /**
                         * generamos la bandera para log
                         */
                        $cookie_flag = new Cookie('cookie_flag'); //make your own cookie
                        $cookie_flag->setExpire(time() + 120 * 60); // 2 minutes for example
                        $cookie_flag->variable_name = 1;
                        $cookie_flag->write();

                        $cookie1 = new Cookie('card_async_flag'); //make your own cookie
                        $cookie1->setExpire(time() + 900); // 15 minutes
                        $cookie1->variable_name = 1;
                        $cookie1->write();

                        $this->saveDataBase('Card Async',$kushkiToken,"", (int)$cart->id,$payment_status,$total,$module_name,$kushkiPaymentMethod,(int)$currency->id,$customer->secure_key,'initializedTransaction');

                        $logger->logInfo('Redirect to card async');
                        PrestaShopLogger::addLog('Redirect to card async');

                        Tools::redirect($transaction_response->getBody()->redirectUrl); // redireccionamos al url de kushki
                        break;
                    case "transfer":
                        $payment_status = Configuration::get('PS_OS_BANKWIRE');

                        /**
                         * generamos la bandera para log
                         */
                        $cookie_flag = new Cookie('cookie_flag'); //make your own cookie
                        $cookie_flag->setExpire(time() + 120 * 60); // 2 minutes for example
                        $cookie_flag->variable_name = 1;
                        $cookie_flag->write();

                        $cookie1 = new Cookie('transfer_flag'); //make your own cookie
                        $cookie1->setExpire(time() + 300); // 2 minutes for example
                        $cookie1->variable_name = 1;
                        $cookie1->write();

                        $this->module->validateOrder((int)$cart->id, $payment_status, $total, $module_name, $message, array(), (int)$currency->id, false, $customer->secure_key);
                        $this->saveDataBase('Transfer',$kushkiToken,"", (int)$cart->id,$payment_status,$total,$module_name,$kushkiPaymentMethod,(int)$currency->id,$customer->secure_key,'initializedTransaction');

                        $logger->logInfo('Redirect to transfer');
                        PrestaShopLogger::addLog('Redirect to transfer');

                        Tools::redirect($transaction_response->getBody()->redirectUrl); // redireccionamos al url de kushki
                        break;
                    case "cash":
                        $payment_status = Configuration::get('PS_OS_BANKWIRE');
                        /**
                         * Guardamos el ticket number para luego utilizarlo en los logs.
                         */
                        $cookie = new Cookie('cookie_ticket_number'); //make your own cookie
                        $cookie->setExpire(time() + 120 * 60); // 2 minutes for example
                        $cookie->variable_name = $transaction_response->getTicketNumber();
                        $cookie->write();
                        /**
                        /**
                         * Guardamos el pdfUrl para luego utilizarlo en el Front.
                         */
                        $cookiePdfUrl = new Cookie('cookie_kushkiPdfUrl'); //make your own cookie
                        $cookiePdfUrl->setExpire(time() + 150); // 2 minutes for example
                        $cookiePdfUrl->variable_name = $transaction_response->getPdfUrl();
                        $cookiePdfUrl->write();

                        $cookie_flag = new Cookie('cookie_flag'); //make your own cookie
                        $cookie_flag->setExpire(time() + 120 * 60); // 2 minutes for example
                        $cookie_flag->variable_name = 1;
                        $cookie_flag->write();

                        /**
                         * Generamos la validación de la orden del carrito
                         */
                        $this->module->validateOrder((int)$cart->id, $payment_status, $total, $module_name, $message, array(), (int)$currency->id, false, $customer->secure_key);

                        /**
                         * Guardamos la informacion en la base de datos
                         */
                        $this->saveDataBase('Cash',$kushkiToken,$transaction_response->getTicketNumber(), (int)$cart->id,$payment_status,$total,$module_name,$kushkiPaymentMethod,(int)$currency->id,$customer->secure_key,'initializedTransaction');

                        /**
                         * Cargamos la pantalla de confirmación del pedido
                         */
                        Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

                        break;
                    case "preauth":
                        if($transaction_response->isPreauthApproval()) {
                            $payment_status = Configuration::get('PS_OS_PREPARATION'); // status del pago

                            PrestaShopLogger::addLog('TicketNumber  preAuth: ' . $transaction_response->getTicketNumber(), 1);

                            /**
                             * Card Guardamos el ticket number para luego utilizarlo en los logs.
                             */
                            $cookie = new Cookie('cookie_ticket_number'); //make your own cookie
                            $cookie->setExpire(time() + 120 * 60); // 2 minutes for example
                            $cookie->variable_name = $transaction_response->getTicketNumber();
                            $cookie->write();

                            /**
                             * generamos la bandera para log
                             */
                            $cookie_flag = new Cookie('cookie_flag'); //make your own cookie
                            $cookie_flag->setExpire(time() + 120 * 60); // 2 minutes for example
                            $cookie_flag->variable_name = 1;
                            $cookie_flag->write();

                            /**
                             * Generamos la validación de la orden del carrito
                             */
                            $this->module->validateOrder((int)$cart->id, $payment_status, $total, $module_name, $message, array(), (int)$currency->id, false, $customer->secure_key);

                            /**
                             * Guardamos la informacion en la base de datos
                             */
                            $this->saveDataBase('Card', $kushkiToken, $transaction_response->getTicketNumber(), (int)$cart->id, $payment_status, $total, $module_name, $kushkiPaymentMethod, (int)$currency->id, $customer->secure_key, 'approvedTransaction');
                            PrestaShopLogger::addLog('Kushki Preauth CORRECTO en orden ' . $this->module->currentOrder . ' Ticket number: ' . $transaction_response->getTicketNumber(), 1);

                            /**
                             * Cargamos la pantalla de confirmación del pedido
                             */
                            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
                        }
                        else {
                            $error_message= "Kushki Error Preauth: " . $transaction_response->getResponseCode() . ": " . $transaction_response->getResponseText();
                            PrestaShopLogger::addLog($error_message, 3);
                            $logger->logError($error_message);

                            /**
                             * Mostramos el mensaje de error en la pantalla.
                             */
                            $ex_detailed_message=$this->trans('An error occurred while processing payment: '.$error_message, array(), 'Modules.Checkpayment.Shop');
                            Tools::redirect(Context::getContext()->link->getModuleLink('kushkipagos', 'error', array('error_msg' => $ex_detailed_message)));
                        }
                        break;
                    default:
                        /**
                         * Card Guardamos el ticket number para luego utilizarlo en los logs.
                         */
                        $cookie = new Cookie('cookie_ticket_number'); //make your own cookie
                        $cookie->setExpire(time() + 120 * 60); // 2 minutes for example
                        $cookie->variable_name = $transaction_response->getTicketNumber();
                        $cookie->write();

                        /**
                         * generamos la bandera para log
                         */
                        $cookie_flag = new Cookie('cookie_flag'); //make your own cookie
                        $cookie_flag->setExpire(time() + 150); // 2 minutes for example
                        $cookie_flag->variable_name = 1;
                        $cookie_flag->write();

                        /**
                         * Generamos la validación de la orden del carrito
                         */
                        $this->module->validateOrder((int)$cart->id, $payment_status, $total, $module_name, $message, array(), (int)$currency->id, false, $customer->secure_key);

                        /**
                         * Guardamos la informacion en la base de datos
                         */
                        $this->saveDataBase('Card',$kushkiToken,$transaction_response->getTicketNumber(), (int)$cart->id,$payment_status,$total,$module_name,$kushkiPaymentMethod,(int)$currency->id,$customer->secure_key,'approvedTransaction');
                        /**
                         * Cargamos la pantalla de confirmación del pedido
                         */
                        Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
                }
            } else {

                /**
                 * Se define el mensaje de error al final.
                 */
                if($transaction_response->getResponseCode() == "PLG007") {
                    $error_message = 'Kushki Error 017: Tarjeta no válida';
                } else {
                    $error_message = "Kushki Error: " . $transaction_response->getResponseCode() . ": " . $transaction_response->getResponseText();
                }// mensaje con el error response de kushki
                PrestaShopLogger::addLog($error_message, 3);
                $logger->logError($error_message);

                /**
                 * Guardamos errores en logs
                 */
                $logger->logError('** Payment FAIL on kushkiPagos, process finished ** ');
                PrestaShopLogger::addLog('Kushki pago FALLIDO, proceso terminado', 3);

                /**
                 * Mostramos el mensaje de error en la pantalla.
                 */
//                $ex_detailed_message='An error occurred while processing payment: '.$error_message;
                $ex_detailed_message=$this->trans('An error occurred while processing payment: '.$error_message, array(), 'Modules.Checkpayment.Shop');
                Tools::redirect(Context::getContext()->link->getModuleLink('kushkipagos', 'error', array('error_msg' => $ex_detailed_message)));
            }
        }else{

            /**
             * Guardamos errores en logs
             */
            $logger->logError(' ** Payment FAIL on kushkiPagos, process finished **  ');
            PrestaShopLogger::addLog('Kushki pago FALLIDO, proceso terminado', 3);

            /**
             * Mostramos el mensaje de error en la pantalla.
             */
            $ex_detailed_message=$this->trans('An error occurred while processing payment: No existe pasarela para realizar el pago ', array(), 'Modules.Checkpayment.Shop');
            Tools::redirect(Context::getContext()->link->getModuleLink('kushkipagos', 'error', array('error_msg' => $ex_detailed_message)));

        }

    }

    private function getPayloadPreAuth($customer, $address, $_currency, $_cart_id, $_total_wt, $_kushkiToken)
    {
        /**
         * Definicion de variables
         */
        $cart_detail = $this->context->cart; //detalle del cart

        $amount = $this->getAmountValues($cart_detail);

        // Metadata Object
        $metadata = array(
            "id_cart" => $_cart_id,
            "plugin" => "PRESTASHOP",
            "city" => $address->city,
            "country" => $address->country,
            "postalCode" => $address->postcode,
            "billingAddressPhone" => $address->phone,
            "phoneMobile" => $address->phone_mobile,
            "province" => "",
            "billingAddress" => $address->address1 . " " . $address->address2,
            "email" => $customer->email,
            "name" => $customer->firstname . " " . $customer->lastname,
            "currency" => $_currency,
            "totalAmount" => (int)$_total_wt,
            "ip" => ""
        );

        // Body Object
        $body = array(
            "token" => $_kushkiToken,
            "orderId" => $_cart_id,
            "amount" => array_merge($amount, array("currency" => $_currency)),
            "channel" => "PRESTASHOP",
            "metadata" => $metadata,
        );

        $customer_details = $this->context->customer;
        $siftFields = $this->getSiftScienceFields($cart_detail, $customer_details, $address);
        $body = array_merge($body, $siftFields);

        return $body;
    }
    /**
     * Backend Integration Kushki
     */
    private function kushki($_currency, $_cart_id, $_kushkiToken, $_kushkiDeferred, $p_kushkiPaymentMethod, $address, $_kushkiDeferredType, $_kushkiMonthsOfGrace)
    {
        /**
         * Definicion de variables
         */
        $cart_detail = $this->context->cart; //detalle del cart
        $customer_details = $this->context->customer;

        $amount = $this->getAmountValues($cart_detail);

        // definimos la metadata
        $metadata = array(
            "plugin" =>'prestashop',
            "city" => $address->city,
            "country" => $address->country,
            "currency" => $_currency,
            "postalCode" =>$address->postcode,
            "billingAddressPhone" => $address->phone_mobile,
            "province" =>Tools::safeOutput($address->id_state),
            "billingAddress" => $address->address1,
            "email" => $customer_details->email,
            "name" => $customer_details->firstname,
            "totalAmount" =>round($amount['subtotalIva'] + $amount['subtotalIva0'] + $amount['iva'] + $amount['ice'], 2),
            "ip" => $_SERVER['REMOTE_ADDR'],
            "orderId" => $_cart_id,
        );

        $obj_contact_details = array(
            "email" => $customer_details->email
        );

        //definimos paymentMethod y activationMethod

        $paymentMethod = "";
        $activationMethod = "";

        switch ( $p_kushkiPaymentMethod ) {
            case "card":
                $paymentMethod = "creditCard";
                $activationMethod = "singlePayment";
                break;
            case "card_async":
                $paymentMethod = "debitCard";
                $activationMethod = "cardAsyncPayment";
                break;
            case "cash":
                $paymentMethod = "cash";
                $activationMethod = "cashPayment";
                break;
            case "transfer":
                $paymentMethod = "transfer";
                $activationMethod = "transferPayment";
                break;
        }


        $protocol = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';

        $baseUri = rtrim(__PS_BASE_URI__, "/");
        // definimos el cuerpo de la petición
        $body = array(
            "token" => $_kushkiToken,
            "orderId" => $_cart_id,
            "amount" => array_merge($amount, array("currency" => $_currency)),
            "metadata" => $metadata,
            "contactDetails" => $obj_contact_details,
            "channel" => "PRESTASHOP",
            "activationMethod" => $activationMethod,
            "paymentMethod" => $paymentMethod,
            "storeDomain" => $protocol."://".$_SERVER['SERVER_NAME'].($baseUri ? $baseUri : '')
        );

        if ($p_kushkiPaymentMethod == "card") {
            $siftFields = $this->getSiftScienceFields($cart_detail, $customer_details, $address);
            $body = array_merge($body, $siftFields);

            if($_kushkiDeferred){
                $deferred = array(
                    'creditType' => strval($_kushkiDeferredType) === "all" || strval($_kushkiDeferredType) ===  ""  ? "000" : strval($_kushkiDeferredType) ,
                    'months' => (int)$_kushkiDeferred,
                    'graceMonths' => $_kushkiMonthsOfGrace ? strval($_kushkiMonthsOfGrace) : "00"
                );
                $body['months'] = (int)$_kushkiDeferred;
                $body['deferred'] = $deferred;
            };
        }

        //agregamos al objeto el body
        $data["body"] = $body;

        $responseRaw = $this->callUrl( self::CHARGE, $data);

        return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
    }

    public function getAmountValues(Cart $cart): array {
        $products = $cart->getProducts();
        $shippingWithTax = $cart->getOrderTotal(true,Cart::ONLY_SHIPPING);
        $shippingWithoutTax = $cart->getOrderTotal(false,Cart::ONLY_SHIPPING);
        $subtotalIva = 0;
        $subtotalIva0 = 0;
        $iva = 0;
        $ice = 0;
        $extra_taxes = array();

        foreach ($products as $item) {
            if ($item["rate"] > 0) {
                $subtotalIva += $item["total"];

                if (stripos($item["tax_name"], "iva") !== false) {
                    $iva += $item["total_wt"] - $item["total"];
                } elseif (stripos($item["tax_name"], "ice") !== false) {
                    $ice += $item["total_wt"] - $item["total"];
                } else {
                    $name_tax = trim( $item['tax_name']);
                    $new_name_tax = str_replace(" ","_", $name_tax);
                    $subtotalIva += $item["total_wt"] - $item["total"]; // total suma de impuestos en ecuador.

                    if(array_key_exists($new_name_tax,$extra_taxes)){
                        $extra_taxes[$new_name_tax] += $item["total_wt"] - $item["total"];
                    }else{
                        $extra_taxes[$new_name_tax] = $item["total_wt"] - $item["total"];
                    }
                }
            } else {
                $subtotalIva0 += $item["total"];
            }
        }

        $shippingTax = $shippingWithTax - $shippingWithoutTax;
        if ($shippingTax > 0) {
            $iva += $shippingTax;
            $subtotalIva += $shippingWithoutTax;
        } else {
            $subtotalIva0 += $shippingWithoutTax;
        }

        $subtotalIva  = round( floatval( $subtotalIva ), 2 );
        $subtotalIva0 = round( floatval( $subtotalIva0 ), 2 );
        $iva          = round( floatval( $iva ), 2 );
        $ice          = round( floatval( $ice ), 2 );

        if (empty($extra_taxes)) {
            return array(
                "subtotalIva"  => $subtotalIva,
                "subtotalIva0" => $subtotalIva0,
                "iva"          => $iva,
                "ice"          => $ice
            );
        }

        return array(
            "subtotalIva"  => $subtotalIva,
            "subtotalIva0" => $subtotalIva0,
            "iva"          => $iva,
            "ice"          => $ice,
            "extraTaxes"   => json_decode(json_encode($extra_taxes, JSON_FORCE_OBJECT))
        );
    }

    private function initPreAuthRequest( $payload )
    {
        $data["body"] = $payload;
        $responseRaw =  $this->callUrl( self::PREAUTH, $data);

        return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
    }

    private function kushkiCapture($_preAuthTicketNumber,$_cart_id)
    {
        $body = array(
            "ticketNumber" => $_preAuthTicketNumber,
            "orderId" => $_cart_id,
        );

        $data["body"] = $body;

        $responseRaw = $this->callUrl( self::CAPTURE, $data );

        return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
    }

    private function callUrl($method,$data){

        $env = Configuration::get('KUSHKIPAGOS_DEV');// entorno de desarrollo del módulo
        $url = !$env ? self::PLUGIN_URL.$method : self::TEST_PLUGIN_URL.$method;

        return \Httpful\Request::post($url)
            ->sendsJson()
            ->withStrictSSL()
            ->addHeaders(array(
                'private-merchant-id' => Configuration::get('KUSHKIPAGOS_PRIVATE_KEY')
            ))
            ->body(json_encode($data["body"]))
            ->send();
    }

    private function saveDataBase($method,$_kushkiToken,$ticketNumber,$_cart_id, $_payment_status, $_total, $_module_name, $_message, $_currency_id,$secure_key,$_status )
    {
        $logger = new FileLogger();
        $logger->setFilename(_PS_ROOT_DIR_ . "/kushkiLogs/" . date('Y-m-d') . ".log");

        $query = 'INSERT INTO `' . _DB_PREFIX_ . 'kushkipagos` (`token`,`ticket_number`,`cart_id`, `payment_status`, `total`, `module_name`, `message`, `currency_id`, `customer_secure_key`, `status`,`order_id`, `updated_at`)
                                 VALUES (  \'' . $_kushkiToken . '\',
                                        \'' . $ticketNumber . '\',
                                        ' . $_cart_id . ',
                                      \'' . $_payment_status . '\',
                                        ' . $_total . ',
                                      \'' . $_module_name . '\',
                                      \'' . $_message . '\',
                                       ' . $_currency_id . ',
                                    \'' . $secure_key . '\',
                                    \'' . $_status . '\',
                                     \'' . $this->module->currentOrder . '\',
                                    \'' . date('Y-m-d H:i:s') . '\'
                         )';

        if (!Db::getInstance()->execute($query))
            $logger->logError('FAIL '.$method.' Transaction saved on database');
        else
            $logger->logInfo($method.' Transaction saved on database');
    }

    private function getSiftScienceFields($cart_detail, $customer_details, $address) {
        foreach ($cart_detail->getProducts() as $k_p_product) {
            $product [] = array(
                "id" => strval($k_p_product['id_product']),
                "title" => $k_p_product['name'],
                "price" => $k_p_product['price'],
                "quantity" => intval($k_p_product['cart_quantity']),
                "sku" => $k_p_product['reference'],
            );
        }
        // definimos el orderDetails
        $orderDetails = array(
            "siteDomain" => $_SERVER['SERVER_NAME'],
            "shippingDetails" => array(
                "firstName" => $customer_details->firstname,
                "lastName" => $customer_details->lastname,
                "phone" => $address->phone,
                "address" => $address->address1,
                "city" => $address->city,
                "region" => Tools::safeOutput($address->id_state),
                "country" => $address->country,
                "zipCode" => $address->postcode
            ),
            "billingDetails" => array(
                "firstName" => $customer_details->firstname,
                "lastName" => $customer_details->lastname,
                "phone" => $address->phone,
                "address" => $address->address1,
                "city" => $address->city,
                "region" => Tools::safeOutput($address->id_state),
                "country" => $address->country,
                "zipCode" => $address->postcode
            )
        );

        //definimos el productDetails

        $obj_product_datails = array(
            "product" => $product
        );

        return array(
            "orderDetails" => $orderDetails,
            "productDetails" => $obj_product_datails
        );
    }
}
