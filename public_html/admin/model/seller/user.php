<?php
class ModelSellerUser extends Model {
	public function addUser($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "seller` SET username = '" . $this->db->escape($data['username']) . "', user_group_id = '" . (int)$data['user_group_id'] . "', salt = '" . $this->db->escape($salt = token(9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "', firstname = '" . $this->db->escape($data['firstname']) . "' ,`desc`='".$this->db->escape($data['desc'])."', price='".$this->db->escape($data['price'])."', prefix_price='".$this->db->escape($data['prefix_price'])."' , lastname = '" . $this->db->escape($data['lastname']) . "',telephone = '" . $this->db->escape($data['telephone']) . "', email = '" . $this->db->escape($data['email']) . "', image = '" . $this->db->escape($data['image']) . "', status = '" . (int)$data['status'] . "', date_added = NOW(), age = '".$this->db->escape($data['age'])."', simple_img = '".$this->db->escape($data['simple_img'])."', bank_name = '".$this->db->escape($data['bank_name'])."', bank_iban = '".$this->db->escape($data['bank_iban'])."', nation_id = '".$this->db->escape($data['nation_id'])."', region = '".$this->db->escape($data['region'])."', instagram = '".$this->db->escape($data['instagram'])."', note = '".$this->db->escape($data['note'])."',show_home='".(int)$data['show_home']."'");

		return $this->db->getLastId();
	}

	public function editUser($user_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "seller` SET username = '" . $this->db->escape($data['username']) . "', `desc`='".$this->db->escape($data['desc'])."' , user_group_id = '" . (int)$data['user_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', price='".$this->db->escape($data['price'])."', prefix_price='".$this->db->escape($data['prefix_price'])."' , lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', image = '" . $this->db->escape($data['image']) . "', banner = '" . $this->db->escape($data['banner']) . "',telephone = '" . $this->db->escape($data['telephone']) . "', status = '" . (int)$data['status'] . "',show_home = '".(int)$data['show_home']."', age = '".$this->db->escape($data['age'])."', simple_img = '".$this->db->escape($data['simple_img'])."', bank_name = '".$this->db->escape($data['bank_name'])."', bank_iban = '".$this->db->escape($data['bank_iban'])."', nation_id = '".$this->db->escape($data['nation_id'])."', region = '".$this->db->escape($data['region'])."', instagram = '".$this->db->escape($data['instagram'])."', note = '".$this->db->escape($data['note'])."' WHERE user_id = '" . (int)$user_id . "'");

		if ($data['password']) {
			$this->db->query("UPDATE `" . DB_PREFIX . "seller` SET salt = '" . $this->db->escape($salt = token(9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "' WHERE user_id = '" . (int)$user_id . "'");
		}
	}

	public function editPassword($user_id, $password) {
		$this->db->query("UPDATE `" . DB_PREFIX . "seller` SET salt = '" . $this->db->escape($salt = token(9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($password)))) . "', code = '' WHERE user_id = '" . (int)$user_id . "'");
	}

	public function editCode($email, $code) {
		$this->db->query("UPDATE `" . DB_PREFIX . "seller` SET code = '" . $this->db->escape($code) . "' WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");
	}

	public function deleteUser($user_id) {
	    $getProducts = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product` WHERE seller_id = '" . (int)$user_id . "'");
        $this->load->model('catalog/product');
	    foreach ($getProducts->rows as $product){
            $this->model_catalog_product->deleteProduct($product['product_id']);
        }
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seller` WHERE user_id = '" . (int)$user_id . "'");
	}

	public function getUser($user_id) {
		$query = $this->db->query("SELECT *, (SELECT ug.name FROM `" . DB_PREFIX . "seller_group` ug WHERE ug.user_group_id = u.user_group_id) AS user_group FROM `" . DB_PREFIX . "seller` u WHERE u.user_id = '" . (int)$user_id . "'");

		return $query->row;
	}

	public function getUserByUsername($username) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seller` WHERE username = '" . $this->db->escape($username) . "'");

		return $query->row;
	}

	public function getUserByEmail($email) {
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "seller` WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row;
	}

	public function getUserByCode($code) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seller` WHERE code = '" . $this->db->escape($code) . "' AND code != ''");

		return $query->row;
	}

	public function getUsers($data = array()) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "seller` WHERE user_id != '' ";

        if (isset($data['filter_name']) && !empty($data['filter_name'])) {
            $sql .= " AND username LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (isset($data['filter_name_name']) && !empty($data['filter_name_name'])) {
            $sql .= " AND CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($data['filter_name_name']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND status = '" . (int)$data['filter_status'] . "'";
        }

		$sort_data = array(
			'username',
			'status',
			'date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY username";
		}

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

	public function getTotalUsers($data = []) {
	    $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "seller` WHERE user_id != ''";

        if (isset($data['filter_name']) && !empty($data['filter_name'])) {
            $sql .= " AND username LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (isset($data['filter_name_name']) && !empty($data['filter_name_name'])) {
            $sql .= " AND CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($data['filter_name_name']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND status = '" . (int)$data['filter_status'] . "'";
        }
        $query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalUsersByGroupId($user_group_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "seller` WHERE user_group_id = '" . (int)$user_group_id . "'");

		return $query->row['total'];
	}

	public function getTotalUsersByEmail($email) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "seller` WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row['total'];
	}
}
