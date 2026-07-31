<?php
class ControllerExtensionPaymentAdvancedBankTransfer extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/advanced_bank_transfer');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_advanced_bank_transfer', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['bank'])) {
			$data['error_bank'] = $this->error['bank'];
		} else {
			$data['error_bank'] = array();
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/advanced_bank_transfer', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/advanced_bank_transfer', 'user_token=' . $this->session->data['user_token'], true);
		$data['user_token'] =$this->session->data['user_token'];

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$this->load->model('localisation/language');

		$data['payment_advanced_bank_transfer'] = array();

		$languages = $this->model_localisation_language->getLanguages();
		
		foreach ($languages as $language) {
			if (isset($this->request->post['payment_advanced_bank_transfer_bank' . $language['language_id']])) {
				$data['payment_advanced_bank_transfer_bank'][$language['language_id']] = $this->request->post['payment_advanced_bank_transfer_bank' . $language['language_id']];
			} else {
				$data['payment_advanced_bank_transfer_bank'][$language['language_id']] = $this->config->get('payment_advanced_bank_transfer_bank' . $language['language_id']);
			}
		}

		$data['languages'] = $languages;

		if (isset($this->request->post['payment_advanced_bank_transfer_total'])) {
			$data['payment_advanced_bank_transfer_total'] = $this->request->post['payment_advanced_bank_transfer_total'];
		} else {
			$data['payment_advanced_bank_transfer_total'] = $this->config->get('payment_advanced_bank_transfer_total');
		}

		if (isset($this->request->post['payment_advanced_bank_transfer_order_status_id_skip'])) {
			$data['payment_advanced_bank_transfer_order_status_id_skip'] = $this->request->post['payment_advanced_bank_transfer_order_status_id_skip'];
		} else {
			$data['payment_advanced_bank_transfer_order_status_id_skip'] = $this->config->get('payment_advanced_bank_transfer_order_status_id_skip');
		}

		if (isset($this->request->post['payment_advanced_bank_transfer_order_status_id_success'])) {
			$data['payment_advanced_bank_transfer_order_status_id_success'] = $this->request->post['payment_advanced_bank_transfer_order_status_id_success'];
		} else {
			$data['payment_advanced_bank_transfer_order_status_id_success'] = $this->config->get('payment_advanced_bank_transfer_order_status_id_success');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_advanced_bank_transfer_geo_zone_id'])) {
			$data['payment_advanced_bank_transfer_geo_zone_id'] = $this->request->post['payment_advanced_bank_transfer_geo_zone_id'];
		} else {
			$data['payment_advanced_bank_transfer_geo_zone_id'] = $this->config->get('payment_advanced_bank_transfer_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');
        $this->load->model('tool/image');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		$banks = $this->getBanks();
		$data['list_banks'] = [];

		foreach ($banks as $bank){
            $data['list_banks'] []= [
                'id'=>$bank['id'],
                'name'=>$bank['name'],
                'image'=>$this->model_tool_image->resize($bank['image'], 50, 50),
                'number'=>$bank['number'],
                'iban'=>$bank['iban'],
            ];
        }


        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 50, 50);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');


		$this->response->setOutput($this->load->view('extension/payment/advanced_bank_transfer', $data));
	}
	public function install(){
	    $this->db->query("CREATE TABLE `".DB_PREFIX."bank_list` (`id` int(11) NOT NULL AUTO_INCREMENT,`name` varchar(255) NOT NULL,`image` varchar(255) DEFAULT NULL,`number` varchar(255) NOT NULL,`iban` varchar(255) NOT NULL,`date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;");
    }
    public function uninstall(){
	    $this->db->query("DROP TABLE `".DB_PREFIX."bank_list`");
    }

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/advanced_bank_transfer')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();

		foreach ($languages as $language) {
			if (empty($this->request->post['payment_advanced_bank_transfer_bank' . $language['language_id']])) {
				$this->error['bank'][$language['language_id']] = $this->language->get('error_bank');
			}
		}

		return !$this->error;
	}

	private function getBanks(){
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."bank_list`");
        return $query->rows;
    }
    private function addBanks($data){
        $this->db->query("INSERT INTO `".DB_PREFIX."bank_list` SET `name`= '".$this->db->escape($data['name'])."',`number`= '".$this->db->escape($data['number'])."',`iban`= '".$this->db->escape($data['iban'])."',`image`= '".$this->db->escape($data['image'])."'");
        return $this->db->getLastId();
    }

    private function deleteBanks($id){
        $this->db->query("DELETE FROM `".DB_PREFIX."bank_list` WHERE `id`= '".(int)$id."'");
        return $this->db->getLastId();
    }

    public function addBank(){
        $this->load->language('extension/payment/advanced_bank_transfer');
        $json['success'] = false;
        $json['post'] = $this->request->post;
        $errors = [];

        if(empty($this->request->post['name'])){
            $errors['name']  = $this->language->get('error_name');
        }
        if(empty($this->request->post['number'])){
            $errors['number']  = $this->language->get('error_number');
        }
        if(empty($this->request->post['iban'])){
            $errors['iban']  = $this->language->get('error_iban');
        }
        if(empty($this->request->post['image'])){
            $errors['image']  = $this->language->get('error_image');
        }

        if(!empty($errors)){
            $json['errors'] = $errors;
        }else{
            $json['success'] = true;
            $json['success_msg'] = $this->language->get('success_msg');
            $this->addBanks($this->request->post);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function deleteBank(){
        $this->load->language('extension/payment/advanced_bank_transfer');
        $json['success'] = false;
        $json['post'] = $this->request->post;
        $errors = [];

        if(empty($this->request->post['id'])){
            $errors['id']  = '';
        }

        if(!empty($errors)){
            //$json['errors'] = $errors;
        }else{
            $json['success'] = true;
            $this->deleteBanks($this->request->post['id']);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}