<?php
namespace Cart;
class Seller {
	private $user_id;
	private $user_group_id;
	private $username;
	private $status;
	private $permission = array();

	public function __construct($registry) {
		$this->db = $registry->get('db');
		$this->request = $registry->get('request');
		$this->session = $registry->get('session');

		if (isset($this->session->data['user_id'])) {
			$user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seller WHERE user_id = '" . (int)$this->session->data['user_id'] . "' AND status = '1'");

			if ($user_query->num_rows) {
				$this->user_id = $user_query->row['user_id'];
				$this->username = $user_query->row['username'];
				$this->user_group_id = $user_query->row['user_group_id'];
				$this->status = $user_query->row['status'];

				$this->db->query("UPDATE " . DB_PREFIX . "seller SET ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "' WHERE user_id = '" . (int)$this->session->data['user_id'] . "'");

				$user_group_query = $this->db->query("SELECT permission FROM " . DB_PREFIX . "seller_group WHERE user_group_id = '" . (int)$user_query->row['user_group_id'] . "'");

				$permissions = json_decode($user_group_query->row['permission'], true);

				if (is_array($permissions)) {
					foreach ($permissions as $key => $value) {
						$this->permission[$key] = $value;
					}
				}
			} else {
				$this->logout();
			}
		}
	}

	public function login($username, $password) {
		$user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seller WHERE email = '" . $this->db->escape($username) . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape($password) . "'))))) OR password = '" . $this->db->escape(md5($password)) . "')");

		if ($user_query->num_rows) {
			$this->session->data['user_id'] = $user_query->row['user_id'];

			$this->user_id = $user_query->row['user_id'];
			$this->username = $user_query->row['username'];
			$this->user_group_id = $user_query->row['user_group_id'];
            $this->status = $user_query->row['status'];


            $user_group_query = $this->db->query("SELECT permission FROM " . DB_PREFIX . "seller_group WHERE user_group_id = '" . (int)$user_query->row['user_group_id'] . "'");

			$permissions = json_decode($user_group_query->row['permission'], true);

			if (is_array($permissions)) {
				foreach ($permissions as $key => $value) {
					$this->permission[$key] = $value;
				}
			}

			return true;
		} else {
			return false;
		}
	}

	public function logout() {
		unset($this->session->data['user_id']);

		$this->user_id = '';
		$this->username = '';
		$this->status = '';
	}

	public function hasPermission($key, $value) {
		if (isset($this->permission[$key])) {
			return in_array($value, $this->permission[$key]);
		} else {
			return false;
		}
	}

	public function isLogged() {
		return $this->user_id;
	}

	public function getId() {
		return $this->user_id;
	}

	public function getUserName() {
		return $this->username;
	}

	public function getStoreId() {
		return (int)$this->user_id;
	}

	public function getGroupId() {
		return $this->user_group_id;
	}
	public function getStatus() {
		return $this->status;
	}
    public function getSellerByEmail($email){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seller WHERE email= '".$this->db->escape($email)."' ");
        return $query->row;
    }

    public function getSellerByTelephone($telephone){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seller WHERE telephone= '".$this->db->escape($telephone)."' ");
        return $query->row;
    }

    public function settingImage($code,$user_id){
        $query = $this->db->query("SELECT * FROM ".DB_PREFIX."upload WHERE code = '".$this->db->escape($code)."'");
        $folder_img = DIR_IMAGE.'catalog/'.$this->getUserName();
        if($query->row){
            $file = DIR_UPLOAD_DIR.$query->row['filename'];
            if(is_file($file)){
                if(!is_dir($folder_img)){
                    mkdir($folder_img . '/' , 0777);
                    chmod($folder_img . '/' , 0777);

                    @touch($folder_img . '/' . 'index.html');
                }
                $newFile = substr(sha1(mt_rand()),17,6).$query->row['name'];
                rename($file, $folder_img."/".$newFile);
                $this->db->query("UPDATE ".DB_PREFIX."seller SET simple_img = '".$this->db->escape('catalog/'.$this->getUserName().'/'.$newFile)."' WHERE user_id = '".(int)$user_id."'");
                $this->db->query("DELETE FROM ".DB_PREFIX."upload WHERE code = '".$this->db->escape($code)."'");
                return 'catalog/'.$this->getUserName().'/'.$newFile;
            }

        }
        return false;


    }
}