<?php


class KushkipagosStatusModuleFrontController extends ModuleFrontController
{
    public $errors;

    public function initContent()
    {
        //set los log del webhook
        $logger = new FileLogger(); //0 == nivel de debug. Sin esto logDebug() no funciona.
        $logger->setFilename(_PS_ROOT_DIR_ . "/kushkiLogs/" . date('Y-m-d') . ".log");

        //headers
        $app_key = $_SERVER['HTTP_X_KUSHKI_KEY'];// public key
        $webhook_signature = $_SERVER ['HTTP_X_KUSHKI_SIGNATURE'];
        $webhook_simple_signature = $_SERVER ['HTTP_X_KUSHKI_SIMPLESIGNATURE'];
        $kushki_id = $_SERVER ['HTTP_X_KUSHKI_ID'];

        //set variables
        $app_secret = Configuration::get('KUSHKIPAGOS_PRIVATE_KEY');
        $ps_status_accept = array("DECLINED", "APPROVAL", "INITIALIZED");

        // decode as associative array
        $body = trim(file_get_contents('php://input'));
        $decoded = json_decode($body, true);

        //variables locales
        $ps_token = $decoded['token'];
        $ps_status = $decoded['status'];


        //$expected_signature = hash_hmac('sha256', $kushki_id, $app_secret, false);

        //$logger->logInfo('------- webhook run:  expected_signature: '.$expected_signature.' webhook_signature-simple: '. $webhook_simple_signature . ' -------');
        //$logger->logInfo('------- webhook run:  expected_signature: '.$expected_signature.' webhook_signature: '. $webhook_signature . ' -------');

        /**
         * para el funcionamiento de la firmas descomentar las lineas 42,90-96
         */

        //if ($webhook_simple_signature == $expected_signature) {

            if (isset($ps_token) and !empty($ps_token)) {

                if(isset($ps_status)  and  !empty($ps_status) and in_array($ps_status, $ps_status_accept)) {

                    // realizamos busqueda en la base de datos
                    $query = 'SELECT * FROM '._DB_PREFIX_.'kushkipagos WHERE `token` = \''.$ps_token.'\' limit 1 ';
                    $pse_array = Db::getInstance()->executeS($query);

                    if(is_array($pse_array) && (count($pse_array) > 0)){

                        if($this->updateStatusOrder($pse_array[0]['order_id'],$ps_status)){
                            $result['code']= 200;
                            $result['status']= 'OK';
                            header("Status: 200 OK");
                            $logger->logInfo('------- webhook run token: '.$ps_token.' new status: '.$ps_status.'-------');
                        }else{
                            $result['code']= 400;
                            $result['message']=$this->errors;
                            foreach ($this->errors as $listError){
                                $logger->logError('------- webhook run token: '.$ps_token.' error: '.$listError.'-------');
                            }
                            header("Status: 400 updateStatusOrder fail!");
                        }
                    }else{
                        $result['code'] = 400;
                        $result['message'] = 'Token no found!';
                        $logger->logError('------- webhook run token: '.$ps_token.' error: '.$result['message'].' -------');
                        header("Status: 400 Token no found!");

                    }
                }else{
                    //error en status
                    $result['code'] = 400;
                    $result['message'] = 'Invalid status!';
                    $logger->logError('------- webhook run token: '.$ps_token.' error: '.$result['message'].' -------');
                    header("Status: 400 Invalid status!");
                }

            } else {
                //error en token
                $result['code'] = 400;
                $result['message'] = 'Invalid token!';
                $logger->logError('------- webhook run token: ' . $ps_token . ' error: ' . $result['message'] . ' -------');
                header("Status: 400 Invalid token!");

            }
        /*} else {
            //error en signature
            $result['code'] = 401;
            $result['message'] = 'Not authenticated!';
            $logger->logError('------- webhook run token: ' . $ps_token . ' error: '.$result['code'].' '. $result['message'] . ' -------');
            header("Status: 401 Not authenticated");
        }*/
        die(Tools::jsonEncode($result));
    }

    private function updateStatusOrder ($order_id,$OrderState): bool{


        if ($OrderState == 'APPROVAL') {
            $payment_status = Configuration::get('PS_OS_PAYMENT');
        } elseif ($OrderState == 'INITIALIZED') {
            $payment_status = Configuration::get('PS_OS_BANKWIRE');
        } elseif ($OrderState == 'DECLINED') {
            $payment_status = Configuration::get('PS_OS_ERROR');
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');
        }

        $order = new Order($order_id);
        $order_state = new OrderState($payment_status);

        if (!Validate::isLoadedObject($order_state)) {
            $this->errors[] = $this->trans('The new order status is invalid.', array(), 'Admin.Orderscustomers.Notification');
        } else {
            $current_order_state = $order->getCurrentOrderState();
            if ($current_order_state->id != $order_state->id) {
                // Create new OrderHistory
                $history = new OrderHistory();
                $history->id_order = $order->id;
                $history->id_employee = 1;

                $use_existings_payment = false;
                if (!$order->hasInvoice()) {
                    $use_existings_payment = true;
                }
                $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);

                $carrier = new Carrier($order->id_carrier, $order->id_lang);
                $templateVars = array();
                if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
                    $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                }

                $history->addWithemail(true, $templateVars);
                // Save all changes
                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                    foreach ($order->getProducts() as $product) {
                        if (StockAvailable::dependsOnStock($product['product_id'])) {
                            StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
                        }
                    }
                }
                $query = 'UPDATE '._DB_PREFIX_.'kushkipagos SET `updated_at` = NOW(), `status` = \''.$OrderState.'\' WHERE `order_id` = '.$order_id.' ';
                Db::getInstance()->execute($query);
                return true;
                //$this->errors[] = $this->trans('An error occurred while changing order status, or we were unable to send an email to the customer.', array(), 'Admin.Orderscustomers.Notification');
            } else {
                $this->errors[] = $this->trans('The order has already been assigned this status.', array(), 'Admin.Orderscustomers.Notification');
            }
        }
        return false;
    }
}