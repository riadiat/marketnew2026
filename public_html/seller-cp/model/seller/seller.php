<?php
class ModelSellerSeller extends Model {
    public function getTotalSeller(){
        $query = $this->db->query("SELECT SUM(price) AS total FROM `" . DB_PREFIX . "seller_amount` WHERE seller_id = '".$this->seller->getStoreId()."' AND is_archive = 0");

        return $query->row['total'];
    }
    public function getTotalOrder(){
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "seller_amount` WHERE seller_id = '".$this->seller->getStoreId()."' AND is_archive = 0");

        return $query->row['total'];
    }
    public function getTotalSellerAdmin(){
        $query = $this->db->query("SELECT SUM(price) AS total FROM `" . DB_PREFIX . "admin_amount` WHERE seller_id = '".$this->seller->getStoreId()."'");

        return $query->row['total'];
    }

    public function getArchiveAmount($data = []){
        $sql = "SELECT * FROM `" . DB_PREFIX . "amount_history` WHERE seller_id='".$this->seller->getStoreId()."'";
        if(!empty($data['filter_month'])){
            $sql .= " AND month='".(int)$this->db->escape($data['filter_month'])."'";
        }
        if(!empty($data['filter_year'])){
            $sql .= " AND year='".$this->db->escape($data['filter_year'])."'";
        }
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalArchiveAmounts(){
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "amount_history` WHERE seller_id='".$this->seller->getStoreId()."'";

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}