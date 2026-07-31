<?php
class ControllerCommonHeader extends Controller {
	public function index() {
		// Analytics
		$this->load->model('setting/extension');

		//$data['analytics'] = array();

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		/*foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}*/

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		}

		$data['title'] = $this->document->getTitle();

		$data['base'] = $server;
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$data['name'] = $this->config->get('config_name');
		$data['ver'] = '0.1.2';

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/header');

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			//$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
		} else {
			//$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));

		$data['home'] = $this->url->link('common/home');
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$data['shopping_cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		$data['search_link'] = $this->url->link('product/search');
		$data['telephone'] = $this->config->get('config_telephone');

		$data['language'] = $this->load->controller('common/language');
		$data['currency'] = $this->load->controller('common/currency');
		$data['search'] = $this->load->controller('common/search');
		$data['cart'] = $this->load->controller('common/cart');
		$data['menu'] = $this->load->controller('common/menu');
		$data['menu_res'] = $this->load->controller('common/menu_res');

        $this->notfiySeller();

		return $this->load->view('common/header', $data);
	}

	private function notfiySeller(){
	    $this->load->model('seller/seller');
	    $this->load->model('catalog/product');
	    $query = $this->db->query("SELECT * FROM `".DB_PREFIX."product` WHERE quantity = 0");
	    $products = $query->rows;
	    $data =[];
	    if($products){
	        foreach ($products as $product){
	            $product_info = $this->model_catalog_product->getProduct($product['product_id']);
                $noify = $this->db->query("SELECT * FROM `".DB_PREFIX."notify_quantity` WHERE product_id = '".(int)$product['product_id']."'");
                if(!$noify->row){
                    $getSeller = $this->model_seller_seller->getSellerById($product_info['seller_id']);
                    if($getSeller){
                        $data [$getSeller['user_id']]['email'] = $getSeller['email'];
                        $data [$getSeller['user_id']]['products'][]= [
                            'product_id'=> $product['product_id'],
                            'name'=> $product_info['name'],
                        ];
                    }

                }
            }


            $mail = new Mail($this->config->get('config_mail_engine'));
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
            $mail->setSubject('تحذير : إنتهاء كمية المنتجات');



            foreach ($data as $item){
                $msg = 'المنتجات التالية انتهت كميتها';
                foreach($item['products'] as $product){
                    $msg .= '<p>عنوان المنتج : '.$product['name'].' : رقم المنتج  '.$product['product_id'].'</p>';
                    $this->db->query("INSERT INTO `".DB_PREFIX."notify_quantity` SET product_id = '".(int)$product['product_id']."'");
                }
                $mail->setHTML($msg);
                $mail->setTo($item['email']);
                $mail->send();
            }
        }
    }
}
