<?php
class ControllerExtensionModuleSls extends Controller {
	private $error = array();


	public function index() {
		$this->load->language('extension/module/sls');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('sls', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['account_id'])) {
			$data['error_account_id'] = $this->error['account_id'];
		} else {
			$data['error_account_id'] = '';
		}

		if (isset($this->error['account_key'])) {
			$data['error_account_key'] = $this->error['account_key'];
		} else {
			$data['error_account_key'] = '';
		}

		if (isset($this->error['api_key'])) {
			$data['error_api_key'] = $this->error['api_key'];
		} else {
			$data['error_api_key'] = '';
		}

		if (isset($this->error['token'])) {
			$data['error_token'] = $this->error['token'];
		} else {
			$data['error_token'] = '';
		}

		if (isset($this->error['status_code'])) {
			$data['error_status_code'] = $this->error['status_code'];
		} else {
			$data['error_status_code'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/sls', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/sls', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/sls', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/sls', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['account_id'])) {
			$data['account_id'] = $this->request->post['account_id'];
		} elseif (!empty($module_info)) {
			$data['account_id'] = $module_info['account_id'];
		} else {
			$data['account_id'] = '';
		}

		if (isset($this->request->post['account_key'])) {
			$data['account_key'] = $this->request->post['account_key'];
		} elseif (!empty($module_info)) {
			$data['account_key'] = $module_info['account_key'];
		} else {
			$data['account_key'] = '';
		}

		
		if (isset($this->request->post['api_key'])) {
			$data['api_key'] = $this->request->post['api_key'];
		} elseif (!empty($module_info)) {
			$data['api_key'] = $module_info['api_key'];
		} else {
			$data['api_key'] = '';
		}

		if (isset($this->request->post['token'])) {
			$data['token'] = $this->request->post['token'];
		} elseif (!empty($module_info)) {
			$data['token'] = $module_info['token'];
		} else {
			$data['token'] = '';
		}

		if (isset($this->request->post['status_code'])) {
			$data['status_code'] = $this->request->post['status_code'];
		} elseif (!empty($module_info)) {
			$data['status_code'] = $module_info['status_code'];
		} else {
			$data['status_code'] = '';
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/sls', $data));
	}



	/* Check user input. */
	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/sls')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['account_id']) {
			$this->error['account_id'] = $this->language->get('error_account_id');
		}

		if (!$this->request->post['account_key']) {
			$this->error['account_key'] = $this->language->get('error_account_key');
		}

		if (!$this->request->post['api_key']) {
			$this->error['api_key'] = $this->language->get('error_api_key');
		}

		if (!$this->request->post['token']) {
			$this->error['token'] = $this->language->get('error_token');
		}

		if (!$this->request->post['status_code']) {
			$this->error['status_code'] = $this->language->get('error_status_code');
		}

		return !$this->error;
	}
	
	  public function install() {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent('send_to_sls', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/sls/on_order_added');

    }

     

    public function uninstall() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEvent('send_to_sls');

    }

	
}