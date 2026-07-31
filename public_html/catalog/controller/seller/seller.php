<?php
class ControllerSellerSeller extends Controller {
    public function index(){
        $this->load->model('seller/seller');
        $this->load->language('seller/seller');
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('tool/image');
        $seller_info = $this->model_seller_seller->getSellerByUsername($this->request->get['username']);
        if(!$seller_info){
            $this->response->redirect($this->url->link('common/home', '', true));
            exit;
        }

        $seller_id = $seller_info['user_id'];

        $this->document->setTitle($seller_info['username']);
        $is_active_product = false;

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $seller_info['username'],
            'href' => HTTPS_SERVER.$seller_info['username']
        );

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'p.sort_order';
        }

        $data['current_url'] = HTTPS_SERVER.$seller_info['username'];

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $is_active_product = true;
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        if (isset($this->request->get['limit'])) {
            $limit = (int)$this->request->get['limit'];
        } else {
            $limit = 12;
        }

        if (isset($this->request->get['filter_category_id'])) {
            $filter_category_id = $this->request->get['filter_category_id'];
            $is_active_product = true;
        } else {
            $filter_category_id = '';
        }
        $filter_data['sort'] = $sort;
        $filter_data['seller_id'] = $seller_id;
        $filter_data['order'] = $order;
        $filter_data['start'] = ($page - 1) * $limit;
        $filter_data['limit'] =$limit;

        $filter_data['filter_category_id'] =$filter_category_id;

        $getProducts = $this->model_catalog_product->getProducts($filter_data);
        $product_total = $this->model_catalog_product->getTotalProducts($filter_data);

        $filter_data['start'] = 0;
        $filter_data['limit'] =99999999999;

        unset($filter_data['filter_category_id']);
        $getAllProducts = $this->model_catalog_product->getProducts($filter_data);


        $url = '';


        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['limit'])) {
            $url .= '&limit=' . $this->request->get['limit'];
        }

        $data['products'] = [];
        $data['categories'] = [];

        foreach ($getProducts as $result) {
            $images = [];
            if ($result['image']) {
                $image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
            } else {
                $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
            }

            $getImages = $this->model_catalog_product->getProductImages($result['product_id']);

            $x = 0;
            foreach ($getImages as $img) {
                $x ++;
                if($x ===1){
                    $images[] = array(
                        'thumb' => $this->model_tool_image->resize($img['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'))
                    );
                }

            }

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }

            if ((float)$result['special']) {
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $special = false;
            }

            if ($this->config->get('config_tax')) {
                $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
            } else {
                $tax = false;
            }

            $data['products'][] = array(
                'product_id'  => $result['product_id'],
                'thumb'       => $image,
                'images'       => $images,
                'name'        => $result['name'],
                'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                'price'       => $price,
                'special'     => $special,
                'tax'         => $tax,
                'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
                'href'        => $this->url->link('product/product' . '&product_id=' . $result['product_id'])
            );
        }

        foreach ($getAllProducts as $product){
            $categories = $this->model_catalog_product->getCategories($product['product_id']);
            if($categories){
                foreach ($categories as $category){
                    $cat = $this->model_catalog_category->getCategory($category['category_id']);
                    if($cat){
                        $data['categories'][$cat['category_id']]=[
                            'category_id'=>$cat['category_id'],
                            'active'=>(isset($this->request->get['filter_category_id'])&&$this->request->get['filter_category_id']==$cat['category_id'])?true:false,
                            'link'=>$this->url->link('seller/seller','&username='.$seller_info['username'].'&filter_category_id='.$cat['category_id']. $url ),
                            'name'=>$cat['name'],
                        ];
                    }

                }

            }

        }



        if ($seller_info['image']) {
            $data['thumb'] = $this->model_tool_image->resize($seller_info['image'], 120,120 );
        } else {
            $data['thumb'] = $this->model_tool_image->resize('placeholder.png',120, 120);
        }

        if ($seller_info['banner']) {
            $data['banner'] = $this->model_tool_image->resize($seller_info['banner'], 1500,450 );
        } else {
            $data['banner'] = $this->model_tool_image->resize('placeholder.png', 1500, 450);
        }

        $data['username'] = $seller_info['username'];
        $data['firstname'] = $seller_info['firstname'];
        $data['lastname'] = $seller_info['lastname'];
        $data['description'] = html_entity_decode($seller_info['desc'], ENT_QUOTES, 'UTF-8');

        if (isset($this->request->get['filter_category_id'])) {
            $url .= '&filter_category_id=' . $this->request->get['filter_category_id'];
        }
        $pagination = new Pagination();
        $pagination->total = $product_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('seller/seller','&username='.$seller_info['username']. $url . '&page={page}');

        $data['pagination'] = $pagination->render();

        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $data['is_active_product'] = $is_active_product;


        $this->response->setOutput($this->load->view('seller/index', $data));
    }
}
