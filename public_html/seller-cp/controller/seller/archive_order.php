<?php
class ControllerSellerArchiveOrder extends Controller {
    public function index(){
        $this->load->language('seller/archive_order');
        $this->load->model('seller/seller');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('seller/archive_order', 'user_token=' . $this->session->data['user_token'], true)
        );


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['months'] =[
            '01','02','03','04','05','06','07','08','09','10','11','12'
        ];
        $data['years'] =array_combine(range(date("Y"), 2018), range(date("Y"), 2018));

        if (isset($this->request->get['filter_month'])) {
            $filter_month = $this->request->get['filter_month'];
        } else {
            $filter_month = '';
        }

        if (isset($this->request->get['filter_year'])) {
            $filter_year = $this->request->get['filter_year'];
        } else {
            $filter_year = '';
        }

        $data['filter_month'] = $filter_month;
        $data['filter_year'] = $filter_year;

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'o.order_id';
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

        if (isset($this->request->get['filter_month'])) {
            $url .= '&filter_month=' . $this->request->get['filter_month'];
        }

        if (isset($this->request->get['filter_year'])) {
            $url .= '&filter_year=' . $this->request->get['filter_year'];
        }

        $filter_data = array(
            'filter_month'        => $filter_month,
            'filter_year'	     => $filter_year,
            'sort'                   => $sort,
            'order'                  => $order,
            'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                  => $this->config->get('config_limit_admin')
        );

        $order_total = $this->model_seller_seller->getTotalArchiveAmounts($filter_data);

        $results = $this->model_seller_seller->getArchiveAmount($filter_data);

        foreach ($results as $result) {
            $data['orders'][] = array(
                'id'      => $result['id'],
                'total'         => $this->currency->format($result['total'], $this->config->get('config_currency')),
                'day'      => $result['day'],
                'month'      => $result['month'],
                'year'      => $result['year'],
                'bank'      => $result['bank'],
                'date'      => $result['month'].'-'.$result['year'],
            );
        }

        $pagination = new Pagination();
        $pagination->total = $order_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('seller/archive_order', $data));
    }
}