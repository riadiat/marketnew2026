<?php
class ControllerEventSine extends Controller {
	public function beforeAddOrder(&$route, &$args, &$output){

    }

    public function afterAddOrderHistory(&$route, &$args, &$output){

	    $this->load->model('checkout/order');
	    $this->load->model('catalog/product');
	    $this->load->model('sine/order');

	    if(isset($args[0])){
            $order_info = $this->model_checkout_order->getOrder($args[0]);
            if($order_info){
                $x = true;
                if ($x) {
                //if (in_array($order_info['order_status_id'], $this->config->get('config_complete_status')) || $order_info['order_status_id'] ==1 || $order_info['order_status_id'] ==2) {
                    $data = [];
                    $getProducts = $this->model_checkout_order->getOrderProducts($order_info['order_id']);
                    foreach ($getProducts as $product){
                        $product_id = $product['product_id'];
                        $product_info = $this->model_catalog_product->getProduct($product_id);
                        if($product_info){
                            $seller_id = $product_info['seller_id'];
                            $data[$seller_id] = [
                                'seller_id'=>$seller_id,
                                'order_id'=>$order_info['order_id'],
                            ];
                        }
                    }

                    foreach ($data as $item){
                        if(isset($item['seller_id'])){
                            $getO = $this->model_sine_order->getOrder($item['order_id'],$item['seller_id']);
                            if(empty($getO)){
                                $this->model_sine_order->addOrder($item);
                            }

                        }

                    }
                }


                if($order_info['order_status_id']==3){
                    $this->saveMoney($order_info);
                }

                if($order_info['order_status_id']==4 || $order_info['order_status_id'] == 10){
                    $this->removeMoney($order_info);
                }
            }
        }
    }

    private function saveMoney($order_info){
        $getProducts = $this->model_checkout_order->getOrderProducts($order_info['order_id']);
	    foreach ($getProducts as $product){
            $product_info = $this->model_catalog_product->getProduct($product['product_id']);
            $seller_id = $product_info['seller_id'];

            //$SellerCalc = SellerCalc::get_instance($this->registry);
            $this->load->library('sellerCalc');
            $SellerCalc = new SellerCalc($this->registry);
            $total_admin_sub = $SellerCalc->calcPrice($seller_id,$product['price']);
            $total_seller_sub = $product['price'] - $total_admin_sub;

            $this->model_sine_order->addAmountOrder($total_admin_sub,$total_seller_sub,$order_info,$seller_id);
        }
    }
    private function removeMoney($order_info){
        $getProducts = $this->model_checkout_order->getOrderProducts($order_info['order_id']);
        foreach ($getProducts as $product){
            $product_info = $this->model_catalog_product->getProduct($product['product_id']);
            $seller_id = $product_info['seller_id'];

            $this->model_sine_order->removeAmountOrder($seller_id,$order_info['order_id']);
        }

    }

}
