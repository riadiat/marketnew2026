<?php
class ModelSellerSeller extends Model {
    public function getSellerByUsername($username){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seller WHERE username='".$this->db->escape($username)."'");

        return $query->row;
    }

    public function getSellerById($seller_id){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seller WHERE user_id='".(int)$seller_id."'");

        return $query->row;
    }

    public function getSellers($data = []){
        $sql = "SELECT * FROM " . DB_PREFIX . "seller WHERE status='1' AND image IS NOT NULL";
        if(isset($data['show_home'])){
            $sql .= " AND show_home= 1";
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
}
