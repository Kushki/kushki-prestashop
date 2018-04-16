<?php

/**
 * Created by PhpStorm.
 * User: Senthil
 */
use PrestaShop\PrestaShop\Core\Payment\PaymentOption; 

if (!defined('_PS_VERSION_'))
    exit;

class KushkiPayment extends PaymentModule {	
	public function __construct() {
		$this->name                   = 'kushkipayment';
        $this->tab = 'payments_gateways';
		$this->version                = '2.0.0';
		$this->author                 = 'Kushki';

		$this->need_instance          = 1;
		$this->ps_versions_compliancy = array( 'min' => '1.7', 'max' => _PS_VERSION_ );
		$this->bootstrap              = true;
        $this->controllers = array('payment', 'validation');		
        $this->currencies = true;
        $this->currencies_mode = 'radio';
	

		parent::__construct();

		$this->displayName = $this->l( 'Kushki' );
		$this->description = $this->l( 'Debit and Credit Card Gateway.' );
		$this->tab = 'payments_gateways';		

		$this->confirmUninstall = $this->l( 'Are you sure you want to uninstall?' );

		if ( ! Configuration::get( 'KUSHKI_TITLE' ) ) {
			$this->warning = $this->l( 'No title provided' );
		}
	}

	public function install() {

		if ( ! parent::install() ||
		     ! $this->registerHook( 'paymentOptions' ) ||
		     ! $this->registerHook( 'orderConfirmation' ) ||
		     ! Configuration::updateValue( 'KUSHKI_TITLE', 'Pago con Tarjeta de Crédito o Débito.' ) ||
		     ! Configuration::updateValue( 'KUSHKI_PUBLIC_KEY', null ) ||
		     ! Configuration::updateValue( 'KUSHKI_PRIVATE_KEY', null ) ||
		     ! Configuration::updateValue( 'KUSHKI_TEST', true )

		) {
			return false;
		}

		return true;

	}

	public function uninstall() {
		if ( ! parent::uninstall() ||
		     ! Configuration::deleteByName( 'KUSHKI_TITLE' ) ||
		     ! Configuration::deleteByName( 'KUSHKI_PUBLIC_KEY' ) ||
		     ! Configuration::deleteByName( 'KUSHKI_PRIVATE_KEY' ) ||
		     ! Configuration::deleteByName( 'KUSHKI_TEST' )
		) {
			return false;
		}

		return true;
	}

	public function getContent() {
		$output = null;

		if ( Tools::isSubmit( 'submit' . $this->name ) ) {
			$ksh_title   = strval( Tools::getValue( 'KUSHKI_TITLE' ) );
			$ksh_public  = strval( Tools::getValue( 'KUSHKI_PUBLIC_KEY' ) );
			$ksh_private = strval( Tools::getValue( 'KUSHKI_PRIVATE_KEY' ) );
			$ksh_test    = strval( Tools::getValue( 'KUSHKI_TEST' ) );
			if ( ! $ksh_title
			     || empty( $ksh_title )
			     || ! Validate::isGenericName( $ksh_title )
			     || ! $ksh_public
			     || empty( $ksh_public )
			     || ! $ksh_private
			     || empty( $ksh_private )
			) {
				$output .= $this->displayError( $this->l( 'Invalid Configuration.' ) );
			} else {
				Configuration::updateValue( 'KUSHKI_TITLE', $ksh_title );
				Configuration::updateValue( 'KUSHKI_PUBLIC_KEY', $ksh_public );
				Configuration::updateValue( 'KUSHKI_PRIVATE_KEY', $ksh_private );
				Configuration::updateValue( 'KUSHKI_TEST', $ksh_test );
				$output .= $this->displayConfirmation( $this->l( 'Settings updated.' ) );
			}
		}

		return $output . $this->displayForm();
	}

	protected function displayForm() {
		// Get default language
		$default_lang = (int) Configuration::get( 'PS_LANG_DEFAULT' );

		// Init Fields form array
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l( 'Settings' ),
			),
			'input'  => array(
				array(
					'type'     => 'text',
					'label'    => $this->l( 'Title' ),
					'name'     => 'KUSHKI_TITLE',
					'size'     => 50,
					'required' => true
				),
				array(
					'type'     => 'text',
					'label'    => $this->l( 'Merchant Public ID' ),
					'name'     => 'KUSHKI_PUBLIC_KEY',
					'size'     => 32,
					'required' => true
				),
				array(
					'type'     => 'text',
					'label'    => $this->l( 'Merchant Private ID' ),
					'name'     => 'KUSHKI_PRIVATE_KEY',
					'size'     => 32,
					'required' => true
				),
				array(
					'type'     => 'switch',
					'label'    => $this->l( 'Test Mode' ),
					'name'     => 'KUSHKI_TEST',
					'required' => true,
					'is_bool'  => true,
					'values'   => array(
						array( 'id' => 'test_on', 'value' => 1, 'label' => $this->l( 'Yes' ) ),
						array( 'id' => 'test_off', 'value' => 0, 'label' => $this->l( 'No' ) ),
					)
				),
			),
			'submit' => array(
				'title' => $this->l( 'Save' ),
				'class' => 'btn btn-default pull-right'
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module          = $this;
		$helper->name_controller = $this->name;
		$helper->token           = Tools::getAdminTokenLite( 'AdminModules' );
		$helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;

		// Language
		$helper->default_form_language    = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
		$helper->title          = $this->displayName;
		$helper->show_toolbar   = true;        // false -> remove toolbar
		$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action  = 'submit' . $this->name;
		$helper->toolbar_btn    = array(
			'save' =>
				array(
					'desc' => $this->l( 'Save' ),
					'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
					          '&token=' . Tools::getAdminTokenLite( 'AdminModules' ),
				),
			'back' => array(
				'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite( 'AdminModules' ),
				'desc' => $this->l( 'Back to list' )
			)
		);

		// Load current value
		$helper->fields_value['KUSHKI_TITLE']       = Configuration::get( 'KUSHKI_TITLE' );
		$helper->fields_value['KUSHKI_PUBLIC_KEY']  = Configuration::get( 'KUSHKI_PUBLIC_KEY' );
		$helper->fields_value['KUSHKI_PRIVATE_KEY'] = Configuration::get( 'KUSHKI_PRIVATE_KEY' );
		$helper->fields_value['KUSHKI_TEST']        = Configuration::get( 'KUSHKI_TEST' );

		return $helper->generateForm( $fields_form );
	}



	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		$payments = $params['objOrder']->getOrderPaymentCollection();

		if (in_array($state, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_OUTOFSTOCK'))))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'id_order' => $params['objOrder']->id,
				'kushki_ticket' => $payments[0]->transaction_id,
				'status' => 'ok',
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}
	
	
	//1.7
	
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        $payment_options = [
            $this->KushkiExternalPaymentOption(),
        ];
        return $payment_options;
    }	

   
	

    public function KushkiExternalPaymentOption()
    {		
		$this->context->smarty->assign( array(
			'this_path'     => $this->_path,
			'this_path_bw'  => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl( true, true ) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
			'kushki_title'  => Configuration::get( 'KUSHKI_TITLE' )
		) );

		

		$paymentController = $this->context->link->getModuleLink($this->name,'payment',array(),true);

        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->l('Pay with Kushki'))
			->setAction($paymentController)
            ->setAdditionalInformation($this->context->smarty->fetch('module:kushkipayment/views/templates/front/payment_infos.tpl'));

        return $newOption;
    }


}