<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');

		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}

		$footer_text = $this->model_catalog_information->getInformation(7);
		if($footer_text){
            $data['footer_text'] = html_entity_decode($footer_text['description'], ENT_QUOTES, 'UTF-8');
        }



		$data['contact'] = $this->url->link('information/contact');
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['tracking'] = $this->url->link('information/tracking');
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/login', '', true);
		$data['special'] = $this->url->link('product/special');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);
		$data['bankconfirm_link'] = $this->url->link('information/bankconfirm', 'is_order=true', true);
		$data['admin_seller_login'] =HTTPS_SERVER.'seller-cp/index.php?route=common/login';
		$data['admin_seller_register'] =HTTPS_SERVER.'seller-cp/index.php?route=seller/register';

		$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));

		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

		$data['scripts'] = $this->document->getScripts('footer');

        $data['telephone'] = $this->config->get('config_telephone');
        $data['email'] = $this->config->get('config_email');

		return $this->load->view('common/footer', $data);
	}
    public function email(){
        $this->load->language('common/footer');
        $this->load->model('account/customer');
        $json = array();
        $json['post'] = $this->request->post;
        $json['email'] = $this->request->post['email'];

        if(empty($this->request->post['email'])){
            $json['code'] = 0;
            $json['error'] = $this->language->get('empty_email');
        }else{
            if(filter_var($this->request->post['email'],FILTER_VALIDATE_EMAIL)){
                $get_email = $this->model_account_customer->get_email($this->request->post['email']);
                if($get_email){
                    $json['error'] = $this->language->get('exist_email');
                    $json['code'] = 0;
                }else{
                    $this->model_account_customer->add_email($this->request->post['email']);
                    $json['success'] = $this->language->get('added_email');
                    $json['code'] = 1;
                }

            }else{
                $json['error'] = $this->language->get('wrong_email');
                $json['code'] = 0;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
