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

use kushki\lib\Transaction;
include_once _PS_MODULE_DIR_.'kushkipagos/classes/kushki/autoload.php';

use PrestaShop\PrestaShop\Core\Domain\Order\CancellationActionType;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}


class Kushkipagos extends PaymentModule
{
    protected $config_form = false;
    private $key_public;
    private $key_private;
    private $status_module= true;
    const PLUGIN_URL = 'https://api.kushkipagos.com/plugins/v1/';
    const TEST_PLUGIN_URL = 'https://api-uat.kushkipagos.com/plugins/v1/';
    const CAPTURE = 'capture';

    public function __construct()
    {
        $this->name = 'kushkipagos';
        $this->tab = 'payments_gateways';
        $this->version = '3.0.11';
        $this->author = 'Kushkipagos';
        $this->need_instance = 0;
        $this->display = 'view';

        $config = Configuration::getMultiple(array('KUSHKIPAGOS_PUBLIC_KEY', 'KUSHKIPAGOS_PRIVATE_KEY'));
        if (isset($config['KUSHKIPAGOS_PUBLIC_KEY'])) {
            $this->key_public = $config['KUSHKIPAGOS_PUBLIC_KEY'];
        }
        if (isset($config['KUSHKIPAGOS_PRIVATE_KEY'])) {
            $this->key_private = $config['KUSHKIPAGOS_PRIVATE_KEY'];
        }


        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('kushkipagos');
        $this->description = $this->l('Módulo de pago por kushki');
        $this->confirmUninstall = $this->l('Está seguro que desea desinstalar el modulo de pagos de kushki?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        if ((!isset($this->key_public) || !isset($this->key_private) || empty($this->key_public) || empty($this->key_private))) {
            $this->warning = 'La llave pública y la llave privada deben ser configuradas antes de utilizar el módulo.' ;
            $this->status_module=false;
        }
        // Si no existen tipos de moneda en su tienda, aparecerá el texto de Warning en el listado de módulos.
        if (!count(Currency::checkPaymentCurrencies($this->id)))
            $this->warning = $this->l('No currency has been set for this module.');

        /**
         * vemos si existe la carpeta para los log, sino la creamos
         */
        $carpeta = _PS_ROOT_DIR_."/kushkiLogs";
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        Configuration::updateValue('KUSHKIPAGOS_LIVE_MODE', false);
        Configuration::updateValue('KUSHKIPAGOS_TRANSFER', false);
        Configuration::updateValue('KUSHKIPAGOS_DEV', true);

        PrestaShopLogger::addLog('Instalación de módulo de pagos Kushki', 2);
        if( parent::install()
            && $this->registerHook('payment')
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionProductCancel')
            && $this->registerHook('actionPaymentConfirmation')
        ){
            return true;
        }else{
            $this->_errors[] = $this->l('No se pudo registrar los hooks payment');
            return false;
        }

        // install DataBase
        if (!$this->installSQL()) {
            $this->_errors[] = $this->l('No se pudo ejecutar el sql');
            return false;
        }

        return true;

    }


    private function installSQL()
    {
        return  Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'kushkipagos (
            `id_kushki_order` INTEGER(11) NOT NULL PRIMARY KEY AUTO_INCREMENT ,
            `token` varchar(100) DEFAULT NULL,
            `ticket_number` varchar(100) DEFAULT NULL,
            `order_id` INTEGER(11) DEFAULT NULL,
            `cart_id` INTEGER(11) DEFAULT NULL,
            `payment_status` VARCHAR(60) DEFAULT NULL,
            `total` FLOAT(11) DEFAULT NULL,
            `module_name` VARCHAR (40)  ,
            `message` VARCHAR (100) ,
            `currency_id` INTEGER (11),
            `customer_secure_key` VARCHAR (60),
             `status` VARCHAR (100),
            `updated_at` DATETIME DEFAULT NULL)
            ENGINE = '._MYSQL_ENGINE_. ' ');
    }

    public function uninstall()
    {
        Configuration::deleteByName('KUSHKIPAGOS_LIVE_MODE');
        Configuration::deleteByName('KUSHKIPAGOS_PUBLIC_KEY');
        Configuration::deleteByName('KUSHKIPAGOS_PRIVATE_KEY');
        Configuration::deleteByName('KUSHKIPAGOS_TRANSFER');
        Configuration::deleteByName('KUSHKIPAGOS_DEV');
        Configuration::deleteByName('KUSHKIPAGOS_RAZON_SOCIAL');


        PrestaShopLogger::addLog('Desinstalación de módulo de pagos Kushki', 2);

        return parent::uninstall();
    }


    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitKushkipagosModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('web_url', $this->context->link->getModuleLink($this->name, 'status', array(), true));
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitKushkipagosModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        if(Currency::getDefaultCurrency()->iso_code=='COP'){
            return $helper->generateForm(array($this->getConfigFormCOP()));
        }else{
            return $helper->generateForm(array($this->getConfigForm()));
        }

    }

    /**
     * Create the structure of your form USD.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(

                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Ingrese la llave pública'),
                        'name' => 'KUSHKIPAGOS_PUBLIC_KEY',
                        'label' => $this->l('Public key'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user-secret"></i>',
                        'desc' => $this->l('Ingrese la llave privada'),
                        'name' => 'KUSHKIPAGOS_PRIVATE_KEY',
                        'label' => $this->l('Private key'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Entorno de pruebas'),
                        'name' => 'KUSHKIPAGOS_DEV',
                        'is_bool' => true,
                        'desc' => $this->l('Usar este módulo en entorno de pruebas'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Create the structure of your form COP.
     */
    protected function getConfigFormCOP()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(

                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Ingrese la llave pública'),
                        'name' => 'KUSHKIPAGOS_PUBLIC_KEY',
                        'label' => $this->l('Public key'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user-secret"></i>',
                        'desc' => $this->l('Ingrese la llave privada'),
                        'name' => 'KUSHKIPAGOS_PRIVATE_KEY',
                        'label' => $this->l('Private key'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-address-card"></i>',
                        'desc' => $this->l('Ingrese la razón social'),
                        'name' => 'KUSHKIPAGOS_RAZON_SOCIAL',
                        'label' => $this->l('Razon Social'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Permitir transferencia'),
                        'name' => 'KUSHKIPAGOS_TRANSFER',
                        'is_bool' => true,
                        'desc' => $this->l('Usar transferencia como forma de pago'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Entorno de pruebas'),
                        'name' => 'KUSHKIPAGOS_DEV',
                        'is_bool' => true,
                        'desc' => $this->l('Usar este módulo en entorno de pruebas'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),

                'submit' => array(
                    'title' => $this->l('Save'),
                ),

            ),

        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'KUSHKIPAGOS_LIVE_MODE' => Configuration::get('KUSHKIPAGOS_LIVE_MODE', true),
            'KUSHKIPAGOS_PUBLIC_KEY' => Configuration::get('KUSHKIPAGOS_PUBLIC_KEY', null),
            'KUSHKIPAGOS_PRIVATE_KEY' => Configuration::get('KUSHKIPAGOS_PRIVATE_KEY', null),
            'KUSHKIPAGOS_RAZON_SOCIAL' => Configuration::get('KUSHKIPAGOS_RAZON_SOCIAL', null),
            'KUSHKIPAGOS_TRANSFER' => Configuration::get('KUSHKIPAGOS_TRANSFER', false),
            'KUSHKIPAGOS_DEV' => Configuration::get('KUSHKIPAGOS_DEV', true)
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {

        $logger = new FileLogger(); //0 == nivel de debug. Sin esto logDebug() no funciona.
        $logger->setFilename(_PS_ROOT_DIR_."/kushkiLogs/".date('Y-m-d').".log");
        $logger->logWarning('Config has been changed');
        PrestaShopLogger::addLog('Actualización de parametros de configuración en módulo kushkipagos', 2);

        if (!$this->installSQL()) {
            $logger->logWarning('FAIL CREATE DATA BASE');
        }else
            $logger->logWarning('DATA BASE CREATE EXECUTE CORRECT');

        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }


    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        $order = $params['order'];

        $logger = new FileLogger(); //0 == nivel de debug. Sin esto logDebug() no funciona.
        $logger->setFilename(_PS_ROOT_DIR_."/kushkiLogs/".date('Y-m-d').".log");


        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')){
            $this->smarty->assign('status', 'ok');


            // asignación sesiones normal
            $cookie = new Cookie('cookie_ticket_number');
            $cookiePdfUrl = new Cookie('cookie_kushkiPdfUrl');
            $cookie_flag = new Cookie('cookie_flag');

            // asignación sesiones transfer
            $cookie_transfer_flag = new Cookie('transfer_flag');
            // asignación sesiones card_async
            $cookie_card_async_flag = new Cookie('card_async_flag');


            if(isset($cookie_card_async_flag->variable_name) and (int)$cookie_card_async_flag->variable_name === 1){
                $this->smarty->assign('is_card_async', $cookie_card_async_flag->variable_name);
                $cookie_card_async_ticketNumber = new Cookie('card_async_ticketNumber');
                $cookie_card_async_status = new Cookie('card_async_status');
                $cookie_card_async_transactionReference = new Cookie('card_async_transactionReference');
                $_body_main_card_async = new Cookie('_body_main_card_async');

                $var_card_async_created = date('Y/m/d', $_body_main_card_async->variable_name);
                $this->smarty->assign('card_async_created', $var_card_async_created);

                if(isset($cookie_card_async_ticketNumber->variable_name) ) {
                    $this->smarty->assign('card_async_ticketNumber', $cookie_card_async_ticketNumber->variable_name);
                }
                if(isset($cookie_card_async_transactionReference->variable_name) ) {
                    $this->smarty->assign('card_async_transactionReference', $cookie_card_async_transactionReference->variable_name);
                }

                if(isset($cookie_flag->variable_name) and (int)$cookie_flag->variable_name===1) {

                    $logger->logInfo(" * Payment status on card_async: ".$cookie_card_async_status->variable_name.", ticket number: " . $cookie_card_async_ticketNumber->variable_name . " whit reference: " . $order->reference . " - orden id: " . $order->id);
                    PrestaShopLogger::addLog('Kushki pago status: '.$cookie_card_async_status->variable_name.' en orden ' . $order->id . ' con referencia ' . $order->reference . ' y numero de ticket: ' . $cookie_card_async_ticketNumber->variable_name, 1);
                    $cookie_flag->variable_name = 0;
                    $cookie_flag->write();
                }
            }


            if(isset($cookie_transfer_flag->variable_name) and (int)$cookie_transfer_flag->variable_name === 1){

                // asignación sesiones transfer
                $cookie_transfer_ticketNumber = new Cookie('transfer_ticketNumber');
                $cookie_transfer_status = new Cookie('transfer_status');
                $cookie_transfer_statusDetail = new Cookie('transfer_statusDetail');
                $cookie_transfer_trazabilityCode = new Cookie('transfer_trazabilityCode');
                $_body_main = new Cookie('_body_main');
                $_bankName = new Cookie('_bankName');

                if(isset($cookie_flag->variable_name) and (int)$cookie_flag->variable_name === 1) {

                    $logger->logInfo(" * Payment status: ".$cookie_transfer_status->variable_name.", ticket number: " . $cookie_transfer_ticketNumber->variable_name . " whit reference: " . $order->reference . " - orden id: " . $order->id);
                    PrestaShopLogger::addLog('Kushki pago status: '.$cookie_transfer_status->variable_name.' en orden ' . $order->id . ' con referencia ' . $order->reference . ' y numero de ticket: ' . $cookie_transfer_ticketNumber->variable_name, 1);
                    $cookie_flag->variable_name = 0;
                    $cookie_flag->write();
                }


                // mandamos variables a la plantilla
                $_body_main_array = unserialize( $_body_main->variable_name);


                //razon social
                $var_transfer_razon_social = Configuration::get('KUSHKIPAGOS_RAZON_SOCIAL');
                if (!$var_transfer_razon_social) {
                    $var_transfer_razon_social = 'Sin razon social';
                }
                $this->smarty->assign('var_transfer_razon_social', $var_transfer_razon_social);

                //estado
                if ($cookie_transfer_status->variable_name == 'approvedTransaction') {
                    $var_transfer_status = 'Aprobada';
                } elseif ($cookie_transfer_status->variable_name == 'initializedTransaction') {
                    $var_transfer_status = 'Pendiente';
                } elseif ($cookie_transfer_status->variable_name == 'declinedTransaction') {
                    $var_transfer_status = 'Denegada';
                } else {
                    $var_transfer_status = 'Denegada';
                }
                $this->smarty->assign('var_transfer_status', $var_transfer_status);

                //documentNumbre
                $var_transfer_documentNumber = $_body_main_array->documentNumber;
                $this->smarty->assign('var_transfer_documentNumber', $var_transfer_documentNumber);

                //bank name
                $var_transfer_bankName = $_bankName->variable_name;
                $this->smarty->assign('var_transfer_bankName', $var_transfer_bankName);

                //paymentDescription
                $var_transfer_paymentDescription = $_body_main_array->paymentDescription;
                $this->smarty->assign('var_transfer_paymentDescription', $var_transfer_paymentDescription);

                //fecha
                $var_transfer_created= date('Y/m/d', $_body_main_array->created/1000);
                $this->smarty->assign('var_transfer_created', $var_transfer_created);


                if(isset($cookie_transfer_flag->variable_name) ) {
                    $this->smarty->assign('is_transfer', $cookie_transfer_flag->variable_name);
                }
                if(isset($cookie_transfer_status->variable_name) ) {
                    $this->smarty->assign('cookie_transfer_status', $cookie_transfer_status->variable_name);
                }
                if(isset($cookie_transfer_ticketNumber->variable_name) ) {
                    $this->smarty->assign('ticketNumber', $cookie_transfer_ticketNumber->variable_name);
                }
                if(isset($cookie_transfer_statusDetail->variable_name) ) {
                    $this->smarty->assign('transfer_statusDetail', $cookie_transfer_statusDetail->variable_name);
                }
                if(isset($cookie_transfer_trazabilityCode->variable_name) ) {
                    $this->smarty->assign('transfer_trazabilityCode', $cookie_transfer_trazabilityCode->variable_name);
                }

            }else {

                if(isset($cookie->variable_name) ) {
                    $this->smarty->assign('ticketNumber', $cookie->variable_name);
                }
                if(isset($cookiePdfUrl->variable_name) ) {
                    $this->smarty->assign('pdfUrl', $cookiePdfUrl->variable_name);
                }

                if (isset($cookie_flag->variable_name) and (int)$cookie_flag->variable_name === 1) {

                    $logger->logInfo(" * Payment DONE, ticket number: " . $cookie->variable_name . " whit reference: " . $order->reference . " - orden id: " . $order->id);
                    PrestaShopLogger::addLog('Kushki pago CORRECTO en orden ' . $order->id . ' con referencia ' . $order->reference . ' y numero de ticket: ' . $cookie->variable_name, 1);
                    $cookie_flag->variable_name = 0;
                    $cookie_flag->write();
                }
            }

        }else{
            $logger->logError(" * FAIL whit reference: ".$order->reference." - orden id: ".$order->id." - amount: ".Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ) );
            PrestaShopLogger::addLog('Kushki pago FALLIDO en orden '.$order->id.' con referencia '.$order->reference, 3);

        }

        $this->smarty->assign(array(
            'shop_name' => $this->context->shop->name,
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total_to_pay' => Tools::displayPrice(
                $params['order']->getOrdersTotalPaid(),
                new Currency($params['order']->id_currency),
                false
            )
        ));

        return $this->fetch('module:kushkipagos/views/templates/hook/confirmation.tpl');
    }


    public function hookPaymentOptions($params)
    {

        if(!$this->status_module){
            return;
        }

        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(
            $this->getTemplateVars($params)
        );


        // definimos que plantilla vamos a usar USD o COP

        $currency_order = new Currency((int)($this->context->cart->id_currency));

        $setAdditionalInformation=$this->fetch('module:kushkipagos/views/templates/hook/kushkiPayment.tpl');

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('Pago con kushki', array(), 'Pago con Kushki'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($setAdditionalInformation);
        return [$newOption];

    }

    public function hookDisplayHeader($params)
    {
        if (!$this->active)
            return;

        $this->context->controller->registerJavascript(
            'cdn-kushki',
            'https://cdn-uat.kushkipagos.com/kushki-checkout.js',
            array('server' => 'remote', 'position' => 'bottom', 'priority' => 150)
        );
    }

    public function hookActionProductCancel($params)
    {
        // let's say you want to check what user action triggered the hook:
        if ($params['action'] === CancellationActionType::STANDARD_REFUND) {
            $order = $params['order'];
            $cart_id = $order->id_cart;
            $cart = new Cart($cart_id);

            // realizamos la consulta de base de datos para el tiketNumber
            $query = 'SELECT * FROM '._DB_PREFIX_.'kushkipagos WHERE `cart_id` = \''.$cart_id.'\' limit 1 ';
            $order_kushki = Db::getInstance()->executeS($query);

            $ticket_number = $order_kushki[0]["ticket_number"];
            $kushkiToken = $order_kushki[0]["token"];

            $currency_order = new Currency((int)((int)$params["order"]->id_currency));

            $amount = $this->getAmountValues($cart);

            $obj_amount = array_merge($amount, array("currency" =>$currency_order->iso_code));
            // llamamos al endpoint de refund
            $refund = $this->callUrlRefund($obj_amount, $ticket_number);

            if ( ! $refund ) {
                throw new PrestaShopException('An error occurred while attempting to create the refund using the payment gateway API.');
            }else{
                if ($refund->isSuccessful()) {
                    //cambiamos el status a refunded
                    $history = new OrderHistory();
                    $history->id_order = (int)$order->id;
                    $history->changeIdOrderState(7, (int)($order->id));
                    $order->setCurrentState(7);
                    PrestaShopLogger::addLog('Kushki Refund CORRECTO en orden' . $order->id . ' Ticket number: ' . $refund->getTicketNumber(), 1);
                    $this->saveDataRefund($kushkiToken, $refund->getTicketNumber(), $cart_id, (int)$order->id, (int)Configuration::get('PS_OS_REFUND'), $order->total_paid, "kushkipagos", "refund", (int)$order->id_currency, $order->secure_key, "initialized" );
                } else {
                    PrestaShopLogger::addLog('Kushki Refund FALLIDO en orden '.$order->id.' Message ' . $refund->getResponseText(), 3);
                    throw new OrderException('Hubo un error en el refund de esta orden.');
                }
            }

        } else if ($params['action'] === CancellationActionType::PARTIAL_REFUND) {
            throw new OrderException('Kushki no puede hacer devoluciones parciales, debe hacer la devolución del total del pedido');
        }
    }

    public function getAmountValues(Cart $cart): array {
        $products = $cart->getProducts();
        $shippingWithTax = $cart->getOrderTotal(true,Cart::ONLY_SHIPPING);
        $shippingWithoutTax = $cart->getOrderTotal(false,Cart::ONLY_SHIPPING);
        $subtotalIva = 0;
        $subtotalIva0 = 0;
        $iva = 0;
        $ice = 0;

        foreach ($products as $item) {
            if ($item["rate"] > 0) {
                $subtotalIva += $item["total"];

                if (stripos($item["tax_name"], "iva") !== false) {
                    $iva += $item["total_wt"] - $item["total"];
                } elseif (stripos($item["tax_name"], "ice") !== false) {
                    $ice += $item["total_wt"] - $item["total"];
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

        return array(
            "subtotalIva"  => $subtotalIva,
            "subtotalIva0" => $subtotalIva0,
            "iva"          => $iva,
            "ice"          => $ice
        );
    }

    public function getTemplateVars($params)
    {
        $cart = $this->context->cart;

        $currency = new Currency((int)($cart->id_currency));


        $publicKey = Configuration::get('KUSHKIPAGOS_PUBLIC_KEY');
        if (!$publicKey) {
            $publicKey = 'No public key configure';
        }

        $privateKey =Configuration::get('KUSHKIPAGOS_PRIVATE_KEY');
        if (!$privateKey) {
            $privateKey = 'No private key configure';
        }
        $totalPagarNativo=Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH));

        $amountValues = $this->getAmountValues(Context::getContext()->cart);

        return [
            'totalDisplay'=>$totalPagarNativo,
            'total_tax'=> Context::getContext()->cart->getTotalShippingCost(),
            'total_wt'=> Context::getContext()->cart->getOrderTotal(true),
            'total_wout'=>Context::getContext()->cart->getOrderTotal(false),
            'cart'=>$cart,
            'params'=>$params,
            'currency' => $currency->iso_code,
            'private_key'=> $privateKey,
            'public_key' => $publicKey,
            'ambiente_kushki'=>Configuration::get('KUSHKIPAGOS_DEV'),
            'pse_url' => Context::getContext()->link->getModuleLink('kushkipagos', 'pse'),
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
            'iso_code' =>  $this->context->language->iso_code,
            'transfer_COP' =>  Configuration::get('KUSHKIPAGOS_TRANSFER'),
            'shipping_order' =>  Context::getContext()->cart->getOrderTotal(true,Cart::ONLY_SHIPPING),
            'subtotalIva' => $amountValues['subtotalIva'],
            'subtotalIva0' => $amountValues['subtotalIva0'],
            'iva' => $amountValues['iva'],
            'ice' => $amountValues['ice']
        ];
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    private function callUrlRefund($amount, $ticketNumber){
        $env = Configuration::get('KUSHKIPAGOS_DEV');// entorno de desarrollo del módulo
        $url = !$env ? self::PLUGIN_URL."refund/".$ticketNumber : self::TEST_PLUGIN_URL."refund/".$ticketNumber;
        $body = array(
            "amount" => $amount,
            "channel" => "PRESTASHOP"
        );

        $responseRaw = \Httpful\Request::delete($url)
            ->sendsJson()
            ->withStrictSSL()
            ->addHeaders(array(
                'private-merchant-id' => Configuration::get('KUSHKIPAGOS_PRIVATE_KEY')
            ))
            ->body(json_encode($body))
            ->send();

        return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);

    }

    private function saveDataRefund($_kushkiToken,$ticketNumber,$_cart_id, $order_id, $_payment_status, $_total, $_module_name, $_message, $_currency_id, $secure_key, $_status ) {
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
                                     \'' . $order_id . '\',
                                    \'' . date('Y-m-d H:i:s') . '\'
                         )';
        Db::getInstance()->execute($query);
    }

    public function hookActionPaymentConfirmation($params)
    {
        $order_id = $params['id_order'];

        $query = 'SELECT * FROM '._DB_PREFIX_.'kushkipagos WHERE `order_id` = \''.$order_id.'\' limit 1 ';
        $order_kushki = Db::getInstance()->executeS($query);

        if($order_kushki[0]['message'] != "preauth") return;

        $order = new Order((int)$order_id);
        $history = new OrderHistory();
        $history->id_order = (int)$order_id;

        $logger = new FileLogger(); //0 == nivel de debug. Sin esto logDebug() no funciona.
        $logger->setFilename(_PS_ROOT_DIR_."/kushkiLogs/".date('Y-m-d').".log");
        $logger->logInfo('------- New kushki capture execution -------');
        PrestaShopLogger::addLog('New kushki capture execution', 1);


        if($order_kushki[0]['status'] == "approvedTransaction"){

            $logger->logInfo('Kushki Capture to order: '.$order_id);

            $capture = $this->processCapture($order_kushki[0]['ticket_number'],$order_id);

            if ($capture->isSuccessful()) {

                $logger->logError('Kushki Capture CORRECTO en orden ' . $order->id . ' Preauth Ticket number: '.$order_kushki[0]['ticket_number'].' Capture Ticket number: ' . $capture->getTicketNumber());
                PrestaShopLogger::addLog('Kushki Capture CORRECTO en orden ' . $order->id . ' Preauth Ticket number: '.$order_kushki[0]['ticket_number'].' Capture Ticket number: ' . $capture->getTicketNumber(), 1);
                $this->saveDataRefund($order_kushki[0]['ticket_number'], $capture->getTicketNumber(), $order_kushki[0]['cart_id'], (int)$order->id, (int)Configuration::get('PS_OS_PAYMENT'), $order->total_paid, "kushkipagos", "capture", (int)$order->id_currency, $order->secure_key, "approvedTransaction" );
                return;
            } else {

                $history->changeIdOrderState(8, (int)($order_id));
                $order->setCurrentState(8);

                $logger->logError('Kushki Capture FALLIDO en orden '.$order_id.' Message ' . $capture->getResponseText());
                PrestaShopLogger::addLog('Kushki Capture FALLIDO en orden '.$order_id.' Message: ' . $capture->getResponseText(), 3);

                throw new OrderException('Hubo un error al procesar el capture de esta orden.');
            }
        }else{
            $history->changeIdOrderState(8, (int)($order_id));
            $order->setCurrentState(8);
            $logger->logError('No se puede ejecutar un capture sobre la orden: '.$order_id.' en preauth con estado fallido');
            PrestaShopLogger::addLog('No se puede ejecutar un capture sobre la orden: '.$order_id.' en preauth con estado fallido', 3);

            throw new OrderException('No se puede ejecutar el capture sobre la orden en preauth con estado fallido.');
        }
    }

    private function processCapture( $ticketNumber,$orderId){
        $env = Configuration::get('KUSHKIPAGOS_DEV');// entorno de desarrollo del módulo
        $url = !$env ? self::PLUGIN_URL.self::CAPTURE : self::TEST_PLUGIN_URL.self::CAPTURE;
        $body = array(
            "ticketNumber" => $ticketNumber,
            "orderId" => strval($orderId),
            "channel" => "PRESTASHOP"
        );

        $data["body"] = $body;
        $responseRaw = \Httpful\Request::post($url)
            ->sendsJson()
            ->withStrictSSL()
            ->addHeaders(array(
                'private-merchant-id' => Configuration::get('KUSHKIPAGOS_PRIVATE_KEY')
            ))
            ->body(json_encode($data["body"]))
            ->send();

        return new Transaction($responseRaw->content_type, $responseRaw->body, $responseRaw->code);
    }
}
