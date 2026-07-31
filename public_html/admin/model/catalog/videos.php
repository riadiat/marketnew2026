<?php
class ModelCatalogVideos extends Model {
	public function addVideo($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "videos SET sort_order = '" . (int)$data['sort_order'] . "', youtube_url='".$this->db->escape($data['youtube_url'])."' , status = '" . (int)$data['status'] . "'");

		$video_id = $this->db->getLastId();

		foreach ($data['videos_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "videos_description SET video_id = '" . (int)$video_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "'");
		}


		$this->cache->delete('videos');

		return $video_id;
	}

	public function editVideo($video_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "videos SET sort_order = '" . (int)$data['sort_order'] . "', youtube_url='".$this->db->escape($data['youtube_url'])."', status = '" . (int)$data['status'] . "' WHERE videos_id = '" . (int)$video_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "videos_description WHERE video_id = '" . (int)$video_id . "'");

		foreach ($data['videos_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "videos_description SET video_id = '" . (int)$video_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "'");
		}


		$this->cache->delete('videos');
	}

	public function deleteVideo($video_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "videos` WHERE videos_id = '" . (int)$video_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "videos_description` WHERE video_id = '" . (int)$video_id . "'");
		$this->cache->delete('videos');
	}

	public function getVideo($video_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "videos WHERE videos_id = '" . (int)$video_id . "'");

		return $query->row;
	}

	public function getVideos($data = array()) {
		if ($data) {
			$sql = "SELECT * FROM " . DB_PREFIX . "videos i LEFT JOIN " . DB_PREFIX . "videos_description id ON (i.videos_id = id.video_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "'";

			$sort_data = array(
				'id.title',
				'i.sort_order'
			);

			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				$sql .= " ORDER BY " . $data['sort'];
			} else {
				$sql .= " ORDER BY id.title";
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
		} else {
			$information_data = $this->cache->get('videos.' . (int)$this->config->get('config_language_id'));

			if (!$information_data) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "videos i LEFT JOIN " . DB_PREFIX . "videos_description id ON (i.videos_id = id.video_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY id.title");

				$information_data = $query->rows;

				$this->cache->set('videos.' . (int)$this->config->get('config_language_id'), $information_data);
			}

			return $information_data;
		}
	}

	public function getVideoDescriptions($video_id) {
		$information_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "videos_description WHERE video_id = '" . (int)$video_id . "'");

		foreach ($query->rows as $result) {
			$information_description_data[$result['language_id']] = array(
				'title'            => $result['title'],
			);
		}

		return $information_description_data;
	}




	public function getTotalVideos() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "videos");

		return $query->row['total'];
	}
}
