<?php
class ModelSaleSeller extends Model {
    public function getTotalSeller(){
        $query = $this->db->query("SELECT SUM(price) AS total FROM `" . DB_PREFIX . "seller_amount`");

        return $query->row['total'];
    }
    public function getTotalOrder(){
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "seller_amount`");

        return $query->row['total'];
    }
    public function getTotalSellerAdmin(){
        $query = $this->db->query("SELECT SUM(price) AS total FROM `" . DB_PREFIX . "admin_amount`");

        return $query->row['total'];
    }

    public function getAmount($data = []){
        $sql = "SELECT * , SUM(price) AS price FROM `" . DB_PREFIX . "admin_amount` WHERE is_archive = 0 GROUP BY seller_id";

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getCountAmountOrders($seller_id){
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "admin_amount` WHERE seller_id = '".(int)$seller_id."'";
        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getTotalAmount(){
        $sql = "SELECT * , SUM(price) AS total FROM `" . DB_PREFIX . "admin_amount` WHERE is_archive = 0 GROUP BY seller_id";

        $query = $this->db->query($sql);

        return count($query->rows);
    }

    public function getTotalOrdersPrice($data){

        $sql = "SELECT SUM(total) AS total FROM `" . DB_PREFIX . "order`";
        if(isset($data['filter_status'])&& !empty($data['filter_status'])){
            $sql .= " WHERE order_status_id = '".(int)$data['filter_status']."'";
        }
        $query = $this->db->query($sql);

        return $query->row['total'];
    }


    public function getArchiveAmount($data = []){
        $sql = "SELECT * , SUM(total) AS total FROM `" . DB_PREFIX . "amount_admin_history` WHERE id != 0 ";
        if(!empty($data['filter_month'])){
            $sql .= " AND month='".(int)$this->db->escape($data['filter_month'])."'";
        }
        if(!empty($data['filter_year'])){
            $sql .= " AND year='".$this->db->escape($data['filter_year'])."'";
        }
        $sql .=" GROUP BY month";
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalArchiveAmounts(){
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "amount_admin_history`";

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}