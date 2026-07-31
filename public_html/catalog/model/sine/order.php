<?php
class ModelSineOrder extends Model {
    public function addOrder($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order_seller` SET order_id = '" . (int)$data['order_id'] . "' , seller_id = '" . (int)$data['seller_id'] . "'");
    }
    public function getOrder($order_id , $seller_id){
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_seller` WHERE order_id = '" . (int)$order_id . "' AND  seller_id = '" . (int)$seller_id . "'");
        return $query->row;
    }
    public function addAmountOrder($total_admin_sub,$total_seller_sub,$order,$seller_id){
        $order_id = $order['order_id'];
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seller_amount` WHERE order_id = '".$order_id."' AND seller_id ='".$seller_id."'");
        $query2 = $this->db->query("SELECT * FROM `" . DB_PREFIX . "admin_amount` WHERE order_id = '".$order_id."' AND seller_id ='".$seller_id."'");
        if(!$query->row){
            $this->db->query("INSERT INTO `" . DB_PREFIX . "seller_amount` SET seller_id='".(int)$seller_id."' , order_id = '".(int)$order_id."', price='".$total_seller_sub."'");
        }
        if(!$query2->row){
            $this->db->query("INSERT INTO `" . DB_PREFIX . "admin_amount` SET seller_id='".(int)$seller_id."' , order_id = '".(int)$order_id."', price='".$total_admin_sub."'");
        }
    }

    public function removeAmountOrder($seller_id,$order_id){
        $this->db->query("DELETE FROM `" . DB_PREFIX . "seller_amount` WHERE seller_id='".(int)$seller_id."' AND order_id = '".(int)$order_id."'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "admin_amount` WHERE seller_id='".(int)$seller_id."' AND order_id = '".(int)$order_id."'");
    }
}