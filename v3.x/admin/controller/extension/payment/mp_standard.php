<?php

require_once '../catalog/controller/extension/payment/mercadopago/mercadopago.php';
require_once '../catalog/controller/extension/payment/mercadopago/mercadopago_util.php';

class ControllerExtensionPaymentMpStandard extends Controller {

	private $_error = array();
	private static $mp_util;
	private static $mp;

	function get_instance_mp_util() {
		if ( $this->mp_util == null ) 
		$this->mp_util = new MPOpencartUtil();
		return $this->mp_util;
	}

	function get_instance_mp() {
		$client_id = $this->config->get( 'mp_standard_client_id' );
		$client_secret = $this->config->get( 'mp_standard_client_secret' );
		if ( isset( $this->request->post['mp_standard_client_id'] ) ) {
			$client_id = $this->request->post['mp_standard_client_id'];
			$client_secret = $this->request->post['mp_standard_client_secret'];
		}
		if ( $this->mp == null ) {
			$this->mp = new MP( $client_id, $client_secret );
		}
		return $this->mp;
	}

	public function index() {

		$this->load->language( 'extension/payment/mp_standard' );
		$this->document->setTitle( $this->language->get( 'heading_title' ) );
		$this->load->model( 'setting/setting' );

		$valid_credentials = true;
		$has_payments_available = true;
		if ( ( $this->request->server['REQUEST_METHOD'] == 'POST' ) ) {
			if ( $this->validate_credentials() ) {
				if ( isset( $this->request->post['mp_standard_methods'] ) ) {
					$names = $this->request->post['mp_standard_methods'];
					$this->request->post['mp_standard_methods'] = '';
					foreach ( $names as $name ) {
						$this->request->post['mp_standard_methods'] .= $name . ',';
					}
					if ( $this->request->post['nro_count_payment_methods'] > count( $names ) ) {
						$this->model_setting_setting->editSetting( 'mp_standard', $this->request->post );
						$this->session->data['success'] = $this->language->get( 'text_success' );
						$this->response->redirect( $this->url->link( 'marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true ) );

						$this->set_settings();
					} else {
						$has_payments_available = false;
					}
				}
			} else {
				$valid_credentials = false;
			}
		}
		
		$data = array(
			// Translations
			'text_edit' => $this->language->get( 'text_edit' ),
			'text_enabled' => $this->language->get( 'text_enabled' ),
			'text_disabled' => $this->language->get( 'text_disabled' ),
			'text_all_zones' => $this->language->get( 'text_all_zones' ),
			'button_save' => $this->language->get( 'button_save' ),
			'button_cancel' => $this->language->get( 'button_cancel' ),
			// Plugin fields
			'header' => $this->load->controller( 'common/header' ),
			'heading_title' => $this->language->get( 'heading_title' ),
			'column_left' => $this->load->controller( 'common/column_left' ),
			'footer' => $this->load->controller( 'common/footer' ),
			'breadcrumbs' => array(
				array(
					'text' => $this->language->get( 'text_home' ),
					'href' => $this->url->link( 'common/dashboard', 'user_token=' . $this->session->data['user_token'], true )
				),
				array(
					'text' => $this->language->get( 'text_extension' ),
					'href' => $this->url->link( 'marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true )
				),
				array(
					'text' => $this->language->get( 'heading_title' ),
					'href' => $this->url->link( 'extension/payment/mp_standard', 'user_token=' . $this->session->data['user_token'], true )
				)
			),
			'action' => $this->url->link( 'extension/payment/mp_standard', 'user_token=' . $this->session->data['user_token'], true ),
			'cancel' => $this->url->link( 'marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true ),
			// Mercado Pago fields
			'mp_standard_status' => ( isset( $this->request->post['mp_standard_status'] ) ) ?
		 		$this->request->post['mp_standard_status'] :
		 		$this->config->get( 'mp_standard_status' ),
			'mp_standard_client_id' => ( isset( $this->request->post['mp_standard_client_id'] ) ) ?
		 		$this->request->post['mp_standard_client_id'] :
		 		$this->config->get( 'mp_standard_client_id' ),
		 	'mp_standard_client_secret' => ( isset( $this->request->post['mp_standard_client_secret'] ) ) ?
				$this->request->post['mp_standard_client_secret'] :
				$this->config->get( 'mp_standard_client_secret' ),
			'error_entry_credentials_basic' => $valid_credentials ? '' : $this->language->get( 'error_entry_credentials_basic' ),
			'mp_standard_country' => ( isset( $this->request->post['mp_standard_country'] ) ) ?
				$this->request->post['mp_standard_country'] :
				$this->config->get( 'mp_standard_country' ),
			'type_checkout' => array( 'Redirect', 'Lightbox', 'Iframe' ),
			'mp_standard_type_checkout' => ( isset( $this->request->post['mp_standard_type_checkout'] ) ) ?
				$this->request->post['mp_standard_type_checkout'] :
				$this->config->get( 'mp_standard_type_checkout' ),
			'category_list' => $this->get_instance_mp_util()->get_category_list( $this->get_instance_mp() ),
			'mp_standard_category_id' => ( isset( $this->request->post['mp_standard_category_id'] ) ?
				$this->request->post['mp_standard_category_id'] :
				$this->config->get( 'mp_standard_category_id' ) ),
			'mp_standard_debug' => ( isset( $this->request->post['mp_standard_debug'] ) ?
				$this->request->post['mp_standard_debug'] :
				$this->config->get( 'mp_standard_debug' ) ),
			'mp_standard_enable_return' => ( isset( $this->request->post['mp_standard_enable_return'] ) ?
				$this->request->post['mp_standard_enable_return'] :
				$this->config->get( 'mp_standard_enable_return' ) ),
			'mp_standard_sandbox' => ( isset( $this->request->post['mp_standard_sandbox'] ) ?
				$this->request->post['mp_standard_sandbox'] :
				$this->config->get( 'mp_standard_sandbox' ) ),
			'installments' => $this->get_instance_mp_util()->get_installments(),
			'mp_standard_installments' => ( isset( $this->request->post['mp_standard_installments'] ) ?
				$this->request->post['mp_standard_installments'] :
				$this->config->get( 'mp_standard_installments' ) ),
			// Oder x Payment statuses
			'mp_standard_order_status_id_completed' => ( isset( $this->request->post['mp_standard_order_status_id_completed'] ) ?
				$this->request->post['mp_standard_order_status_id_completed'] :
				$this->config->get( 'mp_standard_order_status_id_completed' ) ),
			'mp_standard_order_status_id_pending' => ( isset( $this->request->post['mp_standard_order_status_id_pending'] ) ?
				$this->request->post['mp_standard_order_status_id_pending'] :
				$this->config->get( 'mp_standard_order_status_id_pending' ) ),
			'mp_standard_order_status_id_canceled' => ( isset( $this->request->post['mp_standard_order_status_id_canceled'] ) ?
				$this->request->post['mp_standard_order_status_id_canceled'] :
				$this->config->get( 'mp_standard_order_status_id_canceled' ) ),
			'mp_standard_order_status_id_in_process' => ( isset( $this->request->post['mp_standard_order_status_id_in_process'] ) ?
				$this->request->post['mp_standard_order_status_id_in_process'] :
				$this->config->get( 'mp_standard_order_status_id_in_process' ) ),
			'mp_standard_order_status_id_rejected' => ( isset( $this->request->post['mp_standard_order_status_id_rejected'] ) ?
				$this->request->post['mp_standard_order_status_id_rejected'] :
				$this->config->get( 'mp_standard_order_status_id_rejected' ) ),
			'mp_standard_order_status_id_refunded' => ( isset( $this->request->post['mp_standard_order_status_id_refunded'] ) ?
				$this->request->post['mp_standard_order_status_id_refunded'] :
				$this->config->get( 'mp_standard_order_status_id_refunded' ) ),
			'mp_standard_order_status_id_in_mediation' => ( isset( $this->request->post['mp_standard_order_status_id_in_mediation'] ) ?
				$this->request->post['mp_standard_order_status_id_in_mediation'] :
				$this->config->get( 'mp_standard_order_status_id_in_mediation' ) ),
			'mp_standard_order_status_id_chargeback' => ( isset( $this->request->post['mp_standard_order_status_id_chargeback'] ) ?
				$this->request->post['mp_standard_order_status_id_chargeback'] :
				$this->config->get( 'mp_standard_order_status_id_chargeback' ) )
		);

		// Get available payment methods
		$country_id = $this->config->get( 'mp_standard_country' ) != null ?
			$this->config->get( 'mp_standard_country' ) : 'MLA';
		$methods_api = $this->get_instance_mp_util()->get_methods( $country_id, $this->get_instance_mp() );
		$data['methods'] = array();
		$data['mp_standard_methods'] = ( isset( $this->request->post['mp_standard_methods'] ) ) ?
			$this->request->post['mp_standard_methods'] :
			preg_split( '/[\s,]+/', $this->config->get( 'mp_standard_methods' ) );
		foreach ( $methods_api as $method ) {
			if ( $method['id'] != 'account_money' ) {
				$data['methods'][] = $method;
			}
		}
		$data['count_payment_methods'] = count( $data['methods'] );
		$data['error_has_no_payments'] = $has_payments_available ? false : true;
		$data['payment_style'] = isset( $data['methods'] ) && count( $data['methods'] ) > 12 ?
			'float:left; margin:8px;' : 'float:left; margin:12px;';

		// Get order statuses
		$this->load->model( 'localisation/order_status' );
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->response->setOutput( $this->load->view( 'extension/payment/mp_standard', $data ) );
	}

	protected function validate_credentials() {
		return ( $this->request->post['mp_standard_client_id'] && $this->request->post['mp_standard_client_secret'] );
	}

	public function set_settings() {
        $result = $this->get_instance_mp_util()->set_settings(
        	$this->get_instance_mp(),
        	$this->config->get( 'config_email' ), false, false,
        	( $this->request->post['mp_standard_status'] == '1' ? 'true' : 'false' )
    	);
		return $result;  
    }

}
