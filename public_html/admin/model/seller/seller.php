<?php
class ModelSellerSeller extends Model {

    public function getArchiveAmount($data = []){
        $sql = "SELECT * FROM `" . DB_PREFIX . "amount_history` WHERE id != 0 ";
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
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "amount_history`";

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}