<?php
class SellerCalc {
    private $logger;
    private static $instance;

    /**
     * @param  object  $registry  Registry Object
     */
    public static function get_instance($registry) {
        if (is_null(static::$instance)) {
            static::$instance = new static($registry);
        }

        return static::$instance;
    }
    /**
     * @param  object  $registry  Registry Object
     *
     * You could load some useful libraries, few examples:
     *
     *   $registry->get('db');
     *   $registry->get('cache');
     *   $registry->get('session');
     *   $registry->get('config');
     *   and more...
     */
    public function __construct($registry) {
        $this->db = $registry->get('db');
        // load the "Log" library from the "Registry"
        $this->logger = $registry->get('log');
    }
    private function getSellerInfo($seller_id){

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seller WHERE user_id = '" . (int)$seller_id . "'");
        return $query->row;
    }
    public function calcPrice($seller_id,$price){
        $getSellerInfo = $this->getSellerInfo($seller_id);
        $amount =$getSellerInfo['price'];

        if($getSellerInfo['prefix_price']=='-'){
            $total = $price - $amount;
        }else{
            $total = ($amount / 100) * $price;
        }

        return $total;
    }

}