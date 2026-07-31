<?php
class ControllerSaleSeller extends Controller {

	public function index() {
        $this->load->language('sale/seller');
        $this->load->model('sale/seller');
        $this->load->model('seller/user');

        $title = 'الرصيد الشهري بحسب التجار';
        $data['heading_title'] = $title;

        $this->document->setTitle($title);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $title,
            'href' => $this->url->link('sale/seller', 'user_token=' . $this->session->data['user_token'], true)
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

        $order_total = $this->model_sale_seller->getTotalAmount($filter_data);
        $getTotalSellerAdmin = $this->model_sale_seller->getTotalSellerAdmin();

        $data['total_price'] = $this->currency->format($getTotalSellerAdmin, $this->config->get('config_currency'));
        $ensan  = ($getTotalSellerAdmin/100)*5;
        $data['donate'] = $this->currency->format($ensan, $this->config->get('config_currency'));

        $results = $this->model_sale_seller->getAmount($filter_data);

        foreach ($results as $result) {
            $get_seller = $this->model_seller_user->getUser($result['seller_id']);
            $order_number = $this->model_sale_seller->getCountAmountOrders($result['seller_id']);
            $data['orders'][] = array(
                'amount_id'=>$result['id'],
                'id'=>$get_seller['user_id'],
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
        $pagination->url = $this->url->link('sale/seller', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('sale/seller', $data));
	}

	public function send_money(){
        $json['post'] = $this->request->post;
        $json['success'] = false;
        if(isset($this->request->post['bank']) && isset($this->request->post['price'])&& isset($this->request->post['username'])){
            if(!empty($this->request->post['bank']) && !empty($this->request->post['price'])&& !empty($this->request->post['username'])){
                $msg = '<p>مرحباً عزيزي '.$this->request->post['username'].'</p>';
                $msg .= '<p>تم ارسال المبلغ '.$this->request->post['price'].' على حسابك رقم الايصال '.$this->request->post['bank'] . '</p>';
                $msg .= '<p>شكراً لك .. </p>';
                $json['msg'] = $msg;

                $current_day = date('d');
                $month = date('m');
                $year = date('y');

                $seller = $this->db->query("SELECT * , SUM(price) AS total FROM ".DB_PREFIX."seller_amount WHERE seller_id = '".(int)$this->request->post['user_id']."'");
                $getSellers = $this->db->query("SELECT * FROM ".DB_PREFIX."seller_amount WHERE seller_id = '".(int)$this->request->post['user_id']."'");
                foreach ($seller->rows as $row){
                    $this->db->query("INSERT INTO `".DB_PREFIX."amount_history` (`seller_id`,`total`,`day`,`month`,`year`,`bank`)VALUES( '".$row['seller_id']."','".$row['total']."','".$current_day."','".$month."','".$year."','".$this->db->escape($this->request->post['bank'])."')");
                    //$this->db->query("UPDATE `".DB_PREFIX."seller_amount SET is_archive");
                }
                foreach ($getSellers->rows as $s){
                    $this->db->query("UPDATE `".DB_PREFIX."seller_amount` SET is_archive=1 WHERE seller_id = '".$s['seller_id']."' ");
                }
                $admin = $this->db->query("SELECT * , SUM(price) AS total FROM ".DB_PREFIX."admin_amount WHERE seller_id = '".(int)$this->request->post['user_id']."'");
                $getAdmins = $this->db->query("SELECT * FROM ".DB_PREFIX."admin_amount WHERE seller_id = '".(int)$this->request->post['user_id']."'");
                foreach ($admin->rows as $row){
                    $this->db->query("INSERT INTO `".DB_PREFIX."amount_admin_history` (`seller_id`,`total`,`day`,`month`,`year`,`bank`)VALUES( '".$row['seller_id']."','".$row['total']."','".$current_day."','".$month."','".$year."','".$this->db->escape($this->request->post['bank'])."')");
                }

                foreach ($getAdmins->rows as $s){
                    $this->db->query("UPDATE `".DB_PREFIX."admin_amount` SET is_archive=1 WHERE seller_id = '".$s['seller_id']."' ");
                }
                $json['success'] = true;
            }


        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
