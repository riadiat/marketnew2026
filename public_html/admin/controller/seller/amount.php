<?php
class ControllerSellerAmount extends Controller {

	public function index() {
        $this->load->language('sale/seller');
        $this->load->model('seller/amount');
        $this->load->model('seller/user');

        $title = 'رصيد التجار الشهري';
        $data['heading_title'] = $title;

        $this->document->setTitle($title);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $title,
            'href' => $this->url->link('seller/amount', 'user_token=' . $this->session->data['user_token'], true)
        );


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = '';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        $url = '';


        $filter_data = array(
            'sort'                   => $sort,
            'order'                  => $order,
            'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                  => $this->config->get('config_limit_admin')
        );

        $order_total = $this->model_seller_amount->getTotalAmount($filter_data);

        $results = $this->model_seller_amount->getAmount($filter_data);

        foreach ($results as $result) {
            $get_seller = $this->model_seller_user->getUser($result['seller_id']);
            $order_number = $this->model_seller_amount->getCountAmountOrders($result['seller_id']);
            $data['orders'][] = array(
                'id'=>$get_seller['username'],
                'username'=>$get_seller['username'],
                'name'=>$get_seller['first_name'].' '.$get_seller['last_name'],
                'user_link'=>$this->url->link('seller/user/edit', 'user_token=' . $this->session->data['user_token'].'&user_id='.$result['seller_id'], true),
                'order_number'=>$order_number,
                'price'=>$this->currency->format($result['price'], $this->config->get('config_currency')),
            );
        }

        $pagination = new Pagination();
        $pagination->total = $order_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('seller/amount', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('seller/amount', $data));
	}
}
