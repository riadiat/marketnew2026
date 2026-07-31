<?php
class ModelSaleSmsa extends Model {
	public function addSmsa($order_id,$result){
        $this->db->query("INSERT INTO " . DB_PREFIX . "smsa SET order_id='".(int)$order_id."' , aws='".$result."'");
        return $this->db->getLastId();
    }
    public function getSmsaByOrder($order_id){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "smsa WHERE order_id='".(int)$order_id."'");
        return $query->row;
    }
    public function getCityByName($name){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "city WHERE name='".$this->db->escape($name)."'");
        return $query->row;
    }
}