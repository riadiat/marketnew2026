<?php
class ControllerCommonDashboard extends Controller {
	public function index() {
		$this->load->language('common/dashboard');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['user_token'] = $this->session->data['user_token'];

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		// Check install directory exists
		if (is_dir(DIR_APPLICATION . 'install')) {
			$data['error_install'] = $this->language->get('error_install');
		} else {
			$data['error_install'] = '';
		}

		// Dashboard Extensions
		$dashboards = array();

		$this->load->model('setting/extension');

		// Get a list of installed modules
		$extensions = $this->model_setting_extension->getInstalled('dashboard');

		// Add all the modules which have multiple settings for each module
		foreach ($extensions as $code) {
			if ($this->config->get('dashboard_' . $code . '_status') && $this->user->hasPermission('access', 'extension/dashboard/' . $code)) {
				$output = $this->load->controller('extension/dashboard/' . $code . '/dashboard');

				if ($output) {
					$dashboards[] = array(
						'code'       => $code,
						'width'      => $this->config->get('dashboard_' . $code . '_width'),
						'sort_order' => $this->config->get('dashboard_' . $code . '_sort_order'),
						'output'     => $output
					);
				}
			}
		}

		$sort_order = array();

		foreach ($dashboards as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $dashboards);

		// Split the array so the columns width is not more than 12 on each row.
		$width = 0;
		$column = array();
		$data['rows'] = array();

		foreach ($dashboards as $dashboard) {
			$column[] = $dashboard;

			$width = ($width + $dashboard['width']);

			if ($width >= 12) {
				$data['rows'][] = $column;

				$width = 0;
				$column = array();
			}
		}

		if (DIR_STORAGE == DIR_SYSTEM . 'storage/') {
			$data['security'] = $this->load->controller('common/security');
		} else {
			$data['security'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		// Run currency update
		if ($this->config->get('config_currency_auto')) {
			$this->load->model('localisation/currency');

			$this->model_localisation_currency->refresh();
		}


        $this->load->model('seller/user');
        $this->load->model('sale/seller');
        $this->load->model('sale/order');
        $this->load->model('catalog/product');

		$getTotalSellerAdmin = $this->model_sale_seller->getTotalSellerAdmin();

        $filter_orders = [
            'filter_status'=>3
        ];
		$getTotalOrdersPrice = $this->model_sale_seller->getTotalOrdersPrice($filter_orders);

		$getTotalOrders = $this->model_sale_order->getTotalOrders($filter_orders);

		$filter_products = [
		    'filter_status'=>1
        ];
		$getTotalProducts = $this->model_catalog_product->getTotalProducts($filter_products);

		if($getTotalSellerAdmin !='0'){
            $ensan  = ($getTotalSellerAdmin/100)*5;
            $data['ensan']  = $this->number_format_short($this->currency->format($ensan, $this->config->get('config_currency'),'',false));
        }else{
            $data['ensan'] = $this->number_format_short($this->currency->format(0, $this->config->get('config_currency'),'',false));
        }

        $filter_sellers = [
            'filter_status'=>1
        ];
        $getTotalSeller= $this->model_seller_user->getTotalUsers($filter_sellers);

		$data['totalSellerAdmin'] = $this->number_format_short($this->currency->format($getTotalSellerAdmin, $this->config->get('config_currency'),'',false));
		$total_avr = ($getTotalOrders != 0)? $getTotalOrdersPrice/$getTotalOrders:0;
		$data['total_avr'] =   $this->number_format_short($this->currency->format($total_avr, $this->config->get('config_currency'),'',false));
		$data['total_products'] =  $getTotalProducts;
		$data['total_seller'] =  $getTotalSeller;



		$this->response->setOutput($this->load->view('common/dashboard', $data));
	}

	private function number_format_short( $n, $precision = 2 ) {
        if ($n < 900) {
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }
        if ( $precision > 0 ) {
            $dotzero = '.' . str_repeat( '0', $precision );
            $n_format = str_replace( $dotzero, '', $n_format );
        }
        return $n_format . $suffix;
    }
    public function test(){
        $this->load->model('setting/store');

        $store_info = $this->model_setting_store->getStore($this->request->post['store_id']);

        if ($store_info) {
            $store_name = $store_info['name'];
        } else {
            $store_name = $this->config->get('config_name');
        }

        $message  = '<html dir="ltr" lang="en">' . "\n";
        $message .= '  <head>' . "\n";
        $message .= '    <title>' . $this->request->post['subject'] . '</title>' . "\n";
        $message .= '    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n";
        $message .= '  </head>' . "\n";
        $message .= '  <body>test</body>' . "\n";
        $message .= '</html>' . "\n";

        $this->load->model('setting/setting');
        $setting = $this->model_setting_setting->getSetting('config', $this->request->post['store_id']);
        $store_email = isset($setting['config_email']) ? $setting['config_email'] : $this->config->get('config_email');

        $mail = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

        $mail->setTo('ahmed.khalid2200@gmail.com');
        $mail->setFrom($store_email);
        $mail->setSender(html_entity_decode($store_name, ENT_QUOTES, 'UTF-8'));
        $mail->setSubject(html_entity_decode($this->request->post['subject'], ENT_QUOTES, 'UTF-8'));
        $mail->setHtml($message);
        $mail->send();
    }
}
