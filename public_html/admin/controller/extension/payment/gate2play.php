<?php

class ControllerExtensionPaymentGate2play extends Controller {
    private $error = array();

    public function index() {
        
        $this->load->language('extension/payment/gate2play');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $data['heading_title'] = $this->language->get('heading_title');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
                
            //$this->model_setting_setting->editSetting('gate2play',  $this->request->post);
            $this->model_setting_setting->editSetting('payment_gate2play',  $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            //$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
           
        }
        
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => true
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment/gate2play', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/gate2play', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        );   
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }     
        
        if (isset($this->error['permission'])) {
            $data['error_permission'] = $this->error['permission'];
        } else {
            $data['error_permission'] = '';
        }  
        
        if (isset($this->error['heading_title'])) {
            $data['error_heading_title'] = $this->error['heading_title'];
        } else {
            $data['error_heading_title'] = '';
        }   
        
        if (isset($this->error['channel'])) {
            $data['error_channel'] = $this->error['channel'];
        } else {
            $data['error_channel'] = '';
        }   
        
        if (isset($this->error['loginid'])) {
            $data['error_loginid'] = $this->error['loginid'];
        } else {
            $data['error_loginid'] = '';
        }     
        
        if (isset($this->error['password'])) {
            $data['error_password'] = $this->error['password'];
        } else {
            $data['error_password'] = '';
        }  
        
        //-------------------------------------------------------        
        
        $data['text_edit'] = $this->language->get('text_edit');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel'); 
        
        $data['action'] = $this->url->link('extension/payment/gate2play', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);        
        
        $data['entry_heading_title'] = $this->language->get('entry_heading_title');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');        
        
        $data['entry_testmode'] = $this->language->get('entry_testmode');
       
        $data['entry_testmode_off'] = $this->language->get('entry_testmode_off');
        $data['entry_testmode_on'] = $this->language->get('entry_testmode_on');                
        
        $data['entry_trans_type'] = $this->language->get('entry_trans_type');
        $data['entry_all_trans_type'] = $this->get_gate2play_trans_type();
        
        $data['entry_trans_mode'] = $this->language->get('entry_trans_mode');
        $data['entry_all_trans_mode'] = $this->get_gate2play_trans_mode();
        
        $data['entry_base_currency'] = $this->language->get('entry_base_currency');
        $data['entry_all_currencies'] = $this->get_all_currencies();

        $data['entry_channel'] = $this->language->get('entry_channel');
        $data['entry_loginid'] = $this->language->get('entry_loginid');
        $data['entry_password'] = $this->language->get('entry_password');
        
        $data['entry_brands'] = $this->language->get('entry_brands');
        $data['entry_all_brands'] = $this->get_gate2play_payment_methods();
        
        $data['entry_payment_style'] = $this->language->get('Payment Style');
        $data['entry_all_payment_style'] = $this->get_gate2play_payment_style();

        $data['entry_mailerrors'] = str_replace('admin_email', $this->config->get('config_email'), $this->language->get('entry_mailerrors'));
        $data['entry_mailerrors_enable'] = $this->language->get('entry_mailerrors_enable');  
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
                                
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_order_status_failed'] = $this->language->get('entry_order_status_failed');        
        //-----------------------------------------------------------------------
        
        if (isset($this->request->post['payment_gate2play_status'])) {
                $data['payment_gate2play_status'] = $this->request->post['payment_gate2play_status'];
        } else {
                $data['payment_gate2play_status'] = $this->config->get('payment_gate2play_status');
        }
        
        if (isset($this->request->post['payment_gate2play_base_currency'])) {
                $data['payment_gate2play_base_currency'] = $this->request->post['payment_gate2play_base_currency'];
        } else {
                $data['payment_gate2play_base_currency'] = $this->config->get('payment_gate2play_base_currency');
        }
        
        if (isset($this->request->post['payment_gate2play_sort_order'])) {
                $data['payment_gate2play_sort_order'] = $this->request->post['payment_gate2play_sort_order'];
        } else {
                $data['payment_gate2play_sort_order'] = $this->config->get('payment_gate2play_sort_order');
        }        
                
        if (isset($this->request->post['payment_gate2play_testmode'])) {
            $data['payment_gate2play_testmode'] = $this->request->post['payment_gate2play_testmode'];
        } else {
            $data['payment_gate2play_testmode'] = $this->config->get('payment_gate2play_testmode');
        }
        
        if (isset($this->request->post['payment_gate2play_trans_type'])) {
            $data['payment_gate2play_trans_type'] = $this->request->post['payment_gate2play_trans_type'];
        } else {
            $data['payment_gate2play_trans_type'] = $this->config->get('payment_gate2play_trans_type');
        }    
        
        if (isset($this->request->post['payment_gate2play_trans_mode'])) {
            $data['payment_gate2play_trans_mode'] = $this->request->post['payment_gate2play_trans_mode'];
        } else {
            $data['payment_gate2play_trans_mode'] = $this->config->get('payment_gate2play_trans_mode');
        }                
        
        if (isset($this->request->post['payment_gate2play_heading_title'])) {
            $data['payment_gate2play_heading_title'] = $this->request->post['payment_gate2play_heading_title'];
        } else {
            $data['payment_gate2play_heading_title'] = $this->config->get('payment_gate2play_heading_title');
        }   
        
        if (isset($this->request->post['payment_gate2play_channel'])) {
            $data['payment_gate2play_channel'] = $this->request->post['payment_gate2play_channel'];
        } else {
            $data['payment_gate2play_channel'] = $this->config->get('payment_gate2play_channel');
        }   
        
        if (isset($this->request->post['payment_gate2play_loginid'])) {
            $data['payment_gate2play_loginid'] = $this->request->post['payment_gate2play_loginid'];
        } else {
            $data['payment_gate2play_loginid'] = $this->config->get('payment_gate2play_loginid');
        }   
        
        if (isset($this->request->post['payment_gate2play_password'])) {
            $data['payment_gate2play_password'] = $this->request->post['payment_gate2play_password'];
        } else {
            $data['payment_gate2play_password'] = $this->config->get('payment_gate2play_password');
        }                         
        
        if (isset($this->request->post['payment_gate2play_brands'])) {
            $data['payment_gate2play_brands'] = $this->request->post['payment_gate2play_brands'];
        } else {
            $data['payment_gate2play_brands'] = $this->config->get('payment_gate2play_brands');
        }
        
        if (isset($this->request->post['payment_gate2play_payment_style'])) {
            $data['payment_gate2play_payment_style'] = $this->request->post['payment_gate2play_payment_style'];
        } else {
            $data['payment_gate2play_payment_style'] = $this->config->get('payment_gate2play_payment_style');
        }         
        
        if (isset($this->request->post['payment_gate2play_mailerrors'])) {
            $data['payment_gate2play_mailerrors'] = $this->request->post['payment_gate2play_mailerrors'];
        } else {
            $data['payment_gate2play_mailerrors'] = $this->config->get('payment_gate2play_mailerrors');
        }   
        
        if (isset($this->request->post['payment_gate2play_mailerrors_enable'])) {
            $data['payment_gate2play_mailerrors_enable'] = $this->request->post['payment_gate2play_mailerrors_enable'];
        } else {
            $data['payment_gate2play_mailerrors_enable'] = $this->config->get('payment_gate2play_mailerrors_enable');
        }         
        
        $data['payment_gate2play_admin_email'] = $this->config->get('config_email');    
        
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_gate2play_order_status_id'])) {
            $data['payment_gate2play_order_status_id'] = $this->request->post['payment_gate2play_order_status_id'];
        } else {
            $data['payment_gate2play_order_status_id'] = $this->config->get('payment_gate2play_order_status_id');
        }

        if (isset($this->request->post['payment_gate2play_order_status_failed_id'])) {
            $data['payment_gate2play_order_status_failed_id'] = $this->request->post['payment_gate2play_order_status_failed_id'];
        } else {
            $data['payment_gate2play_order_status_failed_id'] = $this->config->get('payment_gate2play_order_status_failed_id');
        }        
               

        $data['text_missing'] = $this->language->get('text_missing');
            
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/gate2play', $data));

        

    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/gate2play')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        
        if (!$this->request->post['payment_gate2play_heading_title']) {
            $this->error['heading_title'] = $this->language->get('error_heading_title');
        }
        
        if (!$this->request->post['payment_gate2play_channel']) {
            $this->error['channel'] = $this->language->get('error_channel');
        } 
        
        if (!$this->request->post['payment_gate2play_loginid']) {
            $this->error['loginid'] = $this->language->get('error_loginid');
        } 
        
        if (!$this->request->post['payment_gate2play_password']) {
            $this->error['password'] = $this->language->get('error_password');
        }        

        if (!$this->error) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    private function get_gate2play_payment_methods() {
        $gate2play_payments = array(
                'VISA' => 'Visa',
                'MASTER' => 'Master Card',
                'MADA' => 'MADA',
                'AMEX' => 'American Express'         
        );  

        return $gate2play_payments;
    }     
    
    
    private function get_gate2play_trans_mode() {
        $gate2play_trans_mode = array(
                'CONNECTOR_TEST' => 'CONNECTOR_TEST',
                'INTEGRATOR_TEST' => 'INTEGRATOR_TEST',
                'LIVE' => 'LIVE'       
        );  

        return $gate2play_trans_mode;
    }      
    
    private function get_gate2play_trans_type() {
        $gate2play_trans_type = array(
                'DB' => 'Debit',
                'PA' => 'Pre-Authorization'     
        );  

        return $gate2play_trans_type;
    }     
    
    private function get_gate2play_payment_style() {
        $gate2play_payment_style = array(
                'card' => 'Card',
                'plain' => 'Plain'     
        );  

        return $gate2play_payment_style;
    }

   private function get_all_currencies(){
	$this->load->model('localisation/currency');
	$currencyArray = [];
	$currencyArray = $this->model_localisation_currency->getCurrencies();
        $all = [];
	foreach( $currencyArray as $currency){
		
                   $all[$currency['code']] = $currency['code'];
                
	}
     return $all;

   }                     
    
}

?>
