<?php
class ControllerCommonColumnLeft extends Controller {
	public function index() {
		if (isset($this->request->get['user_token']) && isset($this->session->data['user_token']) && ($this->request->get['user_token'] == $this->session->data['user_token'])) {
			$this->load->language('common/column_left');

			// Create a 3 level menu array
			// Level 2 can not have children
			
			// Menu
			$data['menus'][] = array(
				'id'       => 'menu-dashboard',
				'icon'	   => 'fa-dashboard',
				'name'	   => $this->language->get('text_dashboard'),
				'href'     => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);

            if ($this->seller->hasPermission('access', 'catalog/product')) {
                $data['menus'][] = array(
                    'id'	   => 'menu-product',
                    'icon'	   => 'fa-shopping-bag',
                    'name'	   => $this->language->get('text_product'),
                    'href'     => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true),
                    'children' => array()
                );
            }

            if ($this->seller->hasPermission('access', 'sale/order')) {
                $data['menus'][] = array(
                    'id'	   => 'menu-order',
                    'icon'	   => 'fa-shopping-cart',
                    'name'	   => $this->language->get('text_order'),
                    'href'     => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true),
                    'children' => array()
                );
            }

            $data['menus'][] = array(
                'id'	   => 'menu-profile',
                'icon'	   => 'fa-gear',
                'name'	   => 'اعدادات ',
                'href'     => $this->url->link('common/profile', 'user_token=' . $this->session->data['user_token'], true),
                'children' => array()
            );

            $data['menus'][] = array(
                'id'	   => 'menu-archive',
                'icon'	   => 'fa-archive',
                'name'	   => 'ارشيف الدفعات ',
                'href'     => $this->url->link('seller/archive_order', 'user_token=' . $this->session->data['user_token'], true),
                'children' => array()
            );

			
			// Marketing
			/*$marketing = array();
			
			if ($this->seller->hasPermission('access', 'marketing/marketing')) {
				$marketing[] = array(
					'name'	   => $this->language->get('text_marketing'),
					'href'     => $this->url->link('marketing/marketing', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()		
				);	
			}
			
			if ($this->seller->hasPermission('access', 'marketing/coupon')) {	
				$marketing[] = array(
					'name'	   => $this->language->get('text_coupon'),
					'href'     => $this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()		
				);	
			}
			
			if ($this->seller->hasPermission('access', 'marketing/contact')) {
				$marketing[] = array(
					'name'	   => $this->language->get('text_contact'),
					'href'     => $this->url->link('marketing/contact', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()		
				);
			}
			
			if ($marketing) {
				$data['menus'][] = array(
					'id'       => 'menu-marketing',
					'icon'	   => 'fa-share-alt', 
					'name'	   => $this->language->get('text_marketing'),
					'href'     => '',
					'children' => $marketing
				);	
			}*/


			
			return $this->load->view('common/column_left', $data);
		}
	}
}