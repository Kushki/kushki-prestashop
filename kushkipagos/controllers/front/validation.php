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

class KushkipagosValidationModuleFrontController extends ModuleFrontController
{
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
        $_internal_status=true;

        /**
         * Set las variables que vamos a utilizar en el proceso.
         */
        $message = null;
        $p_cart_id=Tools::getValue('cart_id');
        $p_total_wt=Tools::getValue('total_wt');
        $p_total_wout=Tools::getValue('total_wout');
        $p_currency=Tools::getValue('currency');
        $p_currency_det=Tools::getValue('currency_det');
        $p_kushkiToken=Tools::getValue('kushkiToken');
        $p_kushkiDeferred=(int)Tools::getValue('kushkiDeferred');
        $p_language=Tools::getValue('language');
        $p_kushkiPaymentMethod=Tools::getValue('kushkiPaymentMethod');
        $p_shipping_order=Tools::getValue('shipping_order');
        $p_env=0; //por defecto en desarrollo
        $p_pse_env=false; // no es pse por defecto
        $p_var_env=Configuration::get('KUSHKIPAGOS_DEV');// entorno de desarrollo del módulo


        if(!$p_var_env){
            $p_env=1;
        }

       if($p_kushkiPaymentMethod=='transfer'){
           $p_pse_env=true;
       }

        /**
         * If the module is not active anymore, no need to process anything.
         */
        if ($this->module->active == false) {
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

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $logger->logError('Error, no object find, redirect ok');
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $module_name = $this->module->displayName;

        /**
         * Vemos a que tienda debemos enviar la transacción.
         */
        if($p_currency==="USD" and $p_currency_det ==="DOLAR"){


            $logger->logInfo('KushkiToken set on USD and kushkiDeferred: '.$p_kushkiDeferred);
            PrestaShopLogger::addLog('kushki USD select to payment, kushkiDeferred: '.$p_kushkiDeferred, 1);

            $transaccion_response =  $this->kushkiUSD($p_cart_id,$p_total_wt,$p_total_wout,$p_kushkiToken,$p_kushkiDeferred,$p_language,$p_env,$p_shipping_order); //tienda USD
        }elseif($p_currency==="COP" and $p_currency_det ==="PESO") {

            //definimos si el pago es por pse o pago con tarjeta
            if ($p_pse_env) {
                // si es true efectuamos pago con pse
                $transaccion_response = $this->kushkiCOP_PSE($p_cart_id, $p_total_wt, $p_total_wout, $p_kushkiToken, $p_kushkiDeferred, $p_language, $p_env,$p_shipping_order); //TIENDA COP CON PSE

                //guardamos información en el log
                $logger->logInfo('KushkiToken set on COP and PSE payment, kushkiDeferred: ' . $p_kushkiDeferred);
                PrestaShopLogger::addLog('kushki COP an PSE select to payment, kushkiDeferred: ' . $p_kushkiDeferred, 1);
            } else {
                // si es false efectuamos pago con tarjeta normal cop
                $transaccion_response = $this->kushkiCOP($p_cart_id, $p_total_wt, $p_total_wout, $p_kushkiToken, $p_kushkiDeferred, $p_language, $p_env,$p_shipping_order); //TIENDA COP

                // guardamos información en el log
                $logger->logInfo('KushkiToken set on COP and kushkiDeferred: ' . $p_kushkiDeferred);
                PrestaShopLogger::addLog('kushki COP select to payment, kushkiDeferred: ' . $p_kushkiDeferred, 1);
            }

        }else{

            $logger->logError('Error on select USD/COP, kushkiDeferred: '.$p_kushkiDeferred);
            PrestaShopLogger::addLog('Kushki Error on select USD/COP', 3);

            $_internal_status=false;  // mandamos mensaje de error si no existe la pasarela de transacción
        }

        /**
         * Comprobamos el estado de la transacción.
         */

        if($_internal_status){
            if ($transaccion_response->isSuccessful()) {

                $payment_status = Configuration::get('PS_OS_PAYMENT'); // status del pago


                if($p_pse_env) {

                    $payment_status = Configuration::get('PS_OS_BANKWIRE');
                    $message = 'transacción con pse';

                    /**
                     * generamos la bandera para log
                     */
                    $cookie_flag = new Cookie('cookie_flag'); //make your own cookie
                    $cookie_flag->setExpire(time() + 120 * 60); // 2 minutes for example
                    $cookie_flag->variable_name = 1;
                    $cookie_flag->write();

                    $cookie1 = new Cookie('pse_flag'); //make your own cookie
                    $cookie1->setExpire(time() + 120 * 60); // 2 minutes for example
                    $cookie1->variable_name = 1;
                    $cookie1->write();

                    $this->module->validateOrder((int)$cart->id, $payment_status, $total, $module_name, $message, array(), (int)$currency->id, false, $customer->secure_key);

                    /**
                     * Guardamos en la base de datos la trasacción
                     */
                    $query = 'INSERT INTO ' . _DB_PREFIX_ . 'kushkipagos
                              (`token`,`cart_id`, `payment_status`, `total`, `module_name`, `message`, `currency_id`, `customer_secure_key`, `status`,`order_id`, `updated_at`)
                                 VALUES (  \'' . $p_kushkiToken . '\',
                                        ' . (int)$cart->id . ',
                                      \'' . $payment_status . '\',
                                        ' . $total . ',
                                      \'' . $module_name . '\',
                                      \'' . $message . '\',
                                       ' . (int)$currency->id . ',
                                    \'' . $customer->secure_key . '\',
                                       \'initializedTransaction\',
                                     \'' . $this->module->currentOrder . '\',
                                    \'' . date('Y-m-d H:i:s') . '\'
                         )';
                    Db::getInstance()->execute($query);


                    Tools::redirect($transaccion_response->getBody()->redirectUrl); // redireccionamos al url de kushki

                }else{

                    /**
                     * Guardamos el ticket number para luego utilizarlo en los logs.
                     */
                    $cookie = new Cookie('cookie_kushkiToken'); //make your own cookie
                    $cookie->setExpire(time() + 120 * 60); // 2 minutes for example
                    $cookie->variable_name = $transaccion_response->getTicketNumber();
                    $cookie->write();


                    /**
                     * generamos la bandera para log
                     */
                    $cookie_flag = new Cookie('cookie_flag'); //make your own cookie
                    $cookie_flag->setExpire(time() + 120 * 60); // 2 minutes for example
                    $cookie_flag->variable_name = 1;
                    $cookie_flag->write();


                    $cookie1 = new Cookie('pse_flag'); //make your own cookie
                    $cookie1->setExpire(time() + 120 * 60); // 2 minutes for example
                    $cookie1->variable_name = 0;
                    $cookie1->write();

                    /**
                     * Generamos la validación de la orden del carrito
                     */
                    $this->module->validateOrder((int)$cart->id, $payment_status, $total, $module_name, $message, array(), (int)$currency->id, false, $customer->secure_key);


                    /**
                     * Cargamos la pantalla de confirmación del pedido
                     */
                    Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

                }
            } else {

                /**
                 * Se define el mensaje de error al final.
                 */
                $error_message= "Kushki Error " . $transaccion_response->getResponseCode() . ": " . $transaccion_response->getResponseText();// mensaje con el error response de kushki
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

    /**
     * Backend Integration Kushki USD
     */

    private function kushkiUSD($_cart_id,$_total_wt,$_total_wout,$_kushkiToken,$_kushkiDeferred,$_language,$_ambiente=0,$_shipping_order=0)
    {
        /**
         * Definicion de variables
         */
        $cart_detail= $this->context->cart; //detalle del cart
        $iva_total=0; //iva
        $ice_total=0; //ice
        $extra_taxes=array(); //otros impuestos
        $amount=0; //amount con iva
        $amount0=0; //amount sin iva

        /**
         * Defiinimos el private-id
         */
        $data["private-merchant-id"] = Configuration::get('KUSHKIPAGOS_PRIVATE_KEY');

        /**
         * Defiinimos la url de acuerdo al ambiente de desarrollo
         * 0 : desarrollo
         * 1 : producción
         */
        if($_ambiente==0) {
            $url = 'https://api-uat.kushkipagos.com/v1/charges';
        }else{
            $url = 'https://api.kushkipagos.com/v1/charges';
        }

        /**
         * Recoremos los productos y vemos los que tienen iva, ice u otros impuestos
         */

        foreach ($cart_detail->getProducts() as $k_p_product){
            if($k_p_product['rate']>0){
                //suma de productos con impuestos
                $amount+=$k_p_product['total'];
                //sacamos la suma independiente de los impuestos
                if(strpos($k_p_product['tax_name'], 'IVA')!==false){
                    $iva_total += $k_p_product['total']*($k_p_product['rate']/100);
                } elseif(strpos($k_p_product['tax_name'], 'ICE')!==false){
                    $ice_total+= $k_p_product['total']*($k_p_product['rate']/100);
                }else{
                    // definimos valor del extra_taxes
                    $name_tax=trim( $k_p_product['tax_name']);
                    $new_name_tax=str_replace(" ","_",$name_tax);
                    $total_sum_int=$k_p_product['total']*$k_p_product['rate']/100;
                    $amount+=$total_sum_int; // total suma de impuestos en ecuador.
                    if(array_key_exists($new_name_tax,$extra_taxes)){
                        $extra_taxes[$new_name_tax]+=$k_p_product['total']*($k_p_product['rate']/100);
                    }else{
                        $extra_taxes[$new_name_tax]=$k_p_product['total']*($k_p_product['rate']/100);
                    }
                }
            }else{
                //suma de productos sin impuestos
                $amount0+=$k_p_product['total'];
            }
        }

        // definimos los meses a diferir
        $meses = (int)$_kushkiDeferred; // Number of months sent from the browser in the kushkiDeferred parameter, converted to Integer

        // definimos la metadata
        $metadata = array("id_cart"=>$_cart_id,"cart_detail"=>$cart_detail);

        // definimos el amount
        $obj_amount =array(
            "subtotalIva" => $amount,
            "subtotalIva0" => $amount0+$_shipping_order,
            "ice" => $ice_total,
            "iva" => $iva_total,
            "currency" => "USD" //usd para ecuador
        );

        // definimos el cuerpo de la petición
        $body = array(
            "token" => $_kushkiToken,
            "amount" => $obj_amount,
            "months" =>$meses,
            "metadata" => $metadata
        );

        //agregamos al objeto el body
        $data["body"] = $body;

        /**
         * Hacemos el llamado al api de kushki
         */
        $responseRaw = \Httpful\Request::post($url)
            ->sendsJson()
            ->withStrictSSL()
            ->addHeaders(array(
                'private-merchant-id' => $data["private-merchant-id"]
            ))
            ->body(json_encode($data["body"]))
            ->send();
        //generamos el objeto de la clase transaction para el uso de los métodos de kushki
        return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
    }

    /**
     * Backend Integration Kushki COP
     */

    private function kushkiCOP($_cart_id,$_total_wt,$_total_wout,$_kushkiToken,$_kushkiDeferred,$_language,$_ambiente=0,$_shipping_order=0)
    {
        /**
         * Definicion de variables
         */
        $cart_detail= $this->context->cart; //detalle del cart
        $iva_total=0; //iva
        $extra_taxes=array(); //otros impuestos
        $amount=0; //amount con impuestos
        $amount0=0; //amount sin impuestos

        /**
         * Defiinimos el private-id
         */
        $data["private-merchant-id"] = Configuration::get('KUSHKIPAGOS_PRIVATE_KEY');

        /**
         * Defiinimos la url de acuerdo al ambiente de desarrollo
         * 0 : desarrollo
         * 1 : producción
         */
        if($_ambiente==0) {
            $url = 'https://api-uat.kushkipagos.com/v1/charges';
        }else{
            $url = 'https://api.kushkipagos.com/v1/charges';
        }

        /**
         * Recoremos los productos y vemos los que tienen iva, ice u otros impuestos
         */
        foreach ($cart_detail->getProducts() as $k_p_product){
            if($k_p_product['rate']>0){
                //suma de productos con iva
                $amount+=$k_p_product['total'];
                //sacamos la suma independiente de los impuestos
                if(strpos($k_p_product['tax_name'], 'IVA')!==false){
                    $iva_total += $k_p_product['total']*($k_p_product['rate']/100);
                }else{
                    // definimos valor del extra_taxes
                    $name_tax=trim( $k_p_product['tax_name']);
                    $new_name_tax=str_replace("","_",$name_tax);
                    if(array_key_exists($new_name_tax,$extra_taxes)){
                        $extra_taxes[$new_name_tax]+=$k_p_product['total']*($k_p_product['rate']/100);
                    }else{
                        $extra_taxes[$new_name_tax]=$k_p_product['total']*($k_p_product['rate']/100);
                    }
                }
            }else{
                //suma de productos sin iva
                $amount0+=$k_p_product['total'];
            }
        }

        // definimos los meses a diferir
        $meses = (int)$_kushkiDeferred; // Number of months sent from the browser in the kushkiDeferred parameter, converted to Integer

        // definimos la metadata
        $metadata = array("id_cart"=>$_cart_id,"cart_detail"=>$cart_detail);

        // definimos el amount
        $obj_amount =array(
            "subtotalIva" => $amount,
            "subtotalIva0" => $amount0+$_shipping_order,
            "iva" => $iva_total,
            "currency" => "COP"
        );

        // anadimos extrataxes si existe algun impuesto nuevo configurado
        if(sizeof($extra_taxes)>0){
            $obj_amount["extraTaxes"]= $extra_taxes;
        }

        // definimos  el cuerpo de la petición
        $body = array(
            "token" => $_kushkiToken,
            "amount" => $obj_amount,
            "months" =>$meses,
            "metadata" => $metadata
        );

        //agregamos al objeto el body
        $data["body"] = $body;

        /**
         * Hacemos el llamado al api
         */
        $responseRaw = \Httpful\Request::post($url)
            ->sendsJson()
            ->withStrictSSL()
            ->addHeaders(array(
                'private-merchant-id' => $data["private-merchant-id"]
            ))
            ->body(json_encode($data["body"]))
            ->send();
        //generamos el objeto de la clase transaction para el uso de los métodos de kushki
        return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
    }

    /**
     * Backend Integration Kushki COP PSE
     */

    private function kushkiCOP_PSE($_cart_id,$_total_wt,$_total_wout,$_kushkiToken,$_kushkiDeferred,$_language,$_ambiente=0,$_shipping_order=0)
    {
        /**
         * Definicion de variables
         */
        $cart_detail= $this->context->cart; //detalle del cart
        $iva_total=0; //iva
        $extra_taxes=array(); //otros impuestos
        $amount=0; //amount con impuestos
        $amount0=0; //amount sin impuestos

        /**
         * Defiinimos el private-id
         */
        $data["private-merchant-id"] = Configuration::get('KUSHKIPAGOS_PRIVATE_KEY');

        /**
         * Defiinimos la url de acuerdo al ambiente de desarrollo
         * 0 : desarrollo
         * 1 : producción
         */
        if($_ambiente==0) {
            $url = 'https://api-uat.kushkipagos.com/transfer/v1/init';
        }else{
            $url = 'https://api.kushkipagos.com/transfer/v1/init';
        }

        /**
         * Recoremos los productos y vemos los que tienen iva, ice u otros impuestos
         */
        foreach ($cart_detail->getProducts() as $k_p_product){
            if($k_p_product['rate']>0){
                //suma de productos con iva
                $amount+=$k_p_product['total'];
                //sacamos la suma independiente de los impuestos
                if(strpos($k_p_product['tax_name'], 'IVA')!==false){
                    $iva_total += $k_p_product['total']*($k_p_product['rate']/100);
                }else{
                    // definimos valor del extra_taxes
                    $name_tax=trim( $k_p_product['tax_name']);
                    $new_name_tax=str_replace("","_",$name_tax);
                    if(array_key_exists($new_name_tax,$extra_taxes)){
                        $extra_taxes[$new_name_tax]+=$k_p_product['total']*($k_p_product['rate']/100);
                    }else{
                        $extra_taxes[$new_name_tax]=$k_p_product['total']*($k_p_product['rate']/100);
                    }
                }
            }else{
                //suma de productos sin iva
                $amount0+=$k_p_product['total'];
            }
        }

        // definimos los meses a diferir
        $meses = (int)$_kushkiDeferred; // Number of months sent from the browser in the kushkiDeferred parameter, converted to Integer

        // definimos la metadata
        $metadata = array("id_cart"=>$_cart_id,"cart_detail"=>$cart_detail);

        // definimos el amount
        $obj_amount =array(
            "subtotalIva" => $amount,
            "subtotalIva0" => $amount0+$_shipping_order,
            "iva" => $iva_total,
        );

        // anadimos extrataxes si existe algun impuesto nuevo configurado
        if(sizeof($extra_taxes)>0){
            $obj_amount["extraTaxes"]= $extra_taxes;
        }

        // definimos  el cuerpo de la petición
        $body = array(
            "token" => $_kushkiToken,
            "amount" => $obj_amount,

        );

        //agregamos al objeto el body
        $data["body"] = $body;
        
        /**
         * Hacemos el llamado al api
         */
        $responseRaw = \Httpful\Request::post($url)
            ->sendsJson()
            ->withStrictSSL()
            ->addHeaders(array(
                'private-merchant-id' => $data["private-merchant-id"]
            ))
            ->body(json_encode($data["body"]))
            ->send();
        //generamos el objeto de la clase transaction para el uso de los métodos de kushki
        return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
    }
}