<?php
class ModelSellerRegister extends Model {
    public function register($data){
        $username = strip_tags($data['username']);
        $username = strtolower(trim($username));

        $status = 0;
        $user_group_id = 1;


        $this->db->query("INSERT INTO `" . DB_PREFIX . "seller` SET username = '" . $username . "', user_group_id = '" . $user_group_id . "', salt = '" . $this->db->escape($salt = token(9)) . "', telephone = '".$this->db->escape($data['telephone'])."' , password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "', firstname = '" . $this->db->escape($data['first_name']) . "', lastname = '" . $this->db->escape($data['last_name']) . "', email = '" . $this->db->escape($data['email']) . "', image = '', status = '" . $status . "', date_added = NOW(), age = '".$this->db->escape($data['age'])."', simple_img = '".$this->db->escape($data['simple_img'])."', bank_name = '".$this->db->escape($data['bank_name'])."', bank_iban = '".$this->db->escape($data['bank_iban'])."', nation_id = '".$this->db->escape($data['nation_id'])."', region = '".$this->db->escape($data['region'])."', instagram = '".$this->db->escape($data['instagram'])."', note = '".$this->db->escape($data['note'])."', `desc` = '".$this->db->escape($data['desc'])."'");
        $user_id = $this->db->getLastId();
        if($data['simple_img']){
            $this->seller->settingImage($data['simple_img'],$user_id);
        }

    }
    public function getSellerByUsername($username){
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seller` WHERE username='".$this->db->escape($username)."'");

        return $query->row;
    }
}
