<?php
class ControllerCommonDashboard extends Controller {
	public function index() {
		$this->load->language('common/dashboard');
		$this->load->model('seller/seller');
        $this->load->model('sale/order');

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

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		// Run currency update
		if ($this->config->get('config_currency_auto')) {
			$this->load->model('localisation/currency');

			$this->model_localisation_currency->refresh();
		}

		$getTotalPrice = $this->model_seller_seller->getTotalSeller();
		$getTotalOrder = $this->model_seller_seller->getTotalOrder();

		$data['totalPrice'] = $this->currency->format($getTotalPrice, $this->config->get('config_currency'));
		$data['totalOrder'] =  $getTotalOrder;

		$data['archive_payment'] = $this->url->link('seller/archive_order', 'user_token=' . $this->session->data['user_token'], true);

        $filter_data = array(
            'sort'  => 'o.date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' => 5
        );

        $results = $this->model_sale_order->getOrders($filter_data);

        foreach ($results as $result) {
            $data['orders'][] = array(
                'order_id'   => $result['order_id'],
                'customer'   => $result['customer'],
                'status'     => $result['order_status'],
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'total'      => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                'view'       => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true),
            );
        }

		$this->response->setOutput($this->load->view('common/dashboard', $data));
	}
}