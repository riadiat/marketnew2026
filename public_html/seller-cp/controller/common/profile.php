<?php
class ControllerCommonProfile extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('common/profile');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('user/user');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$user_data = array_merge($this->request->post, array(
				'user_group_id' => $this->seller->getGroupId(),
				'status'        => 1,
			));

			$this->model_user_user->editUser($this->seller->getId(), $user_data);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('common/profile', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['username'])) {
			$data['error_username'] = $this->error['username'];
		} else {
			$data['error_username'] = '';
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		if (isset($this->error['confirm'])) {
			$data['error_confirm'] = $this->error['confirm'];
		} else {
			$data['error_confirm'] = '';
		}

		if (isset($this->error['firstname'])) {
			$data['error_firstname'] = $this->error['firstname'];
		} else {
			$data['error_firstname'] = '';
		}

		if (isset($this->error['lastname'])) {
			$data['error_lastname'] = $this->error['lastname'];
		} else {
			$data['error_lastname'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

        if (isset($this->error['age'])) {
            $data['error_age'] = $this->error['age'];
        } else {
            $data['error_age'] = '';
        }

        if (isset($this->error['simple_img'])) {
            $data['error_simple_img'] = $this->error['simple_img'];
        } else {
            $data['error_simple_img'] = '';
        }
        if (isset($this->error['image'])) {
            $data['error_image'] = $this->error['image'];
        } else {
            $data['error_image'] = '';
        }

        if (isset($this->error['bank_name'])) {
            $data['error_bank_name'] = $this->error['bank_name'];
        } else {
            $data['error_bank_name'] = '';
        }

        if (isset($this->error['bank_iban'])) {
            $data['error_bank_iban'] = $this->error['bank_iban'];
        } else {
            $data['error_bank_iban'] = '';
        }

        if (isset($this->error['nation_id'])) {
            $data['error_nation_id'] = $this->error['nation_id'];
        } else {
            $data['error_nation_id'] = '';
        }

        if (isset($this->error['region'])) {
            $data['error_region'] = $this->error['region'];
        } else {
            $data['error_region'] = '';
        }
        $this->load->model('tool/image');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('common/profile', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('common/profile', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			$user_info = $this->model_user_user->getUser($this->seller->getId());
		}

		if (isset($this->request->post['username'])) {
			$data['username'] = $this->request->post['username'];
		} elseif (!empty($user_info)) {
			$data['username'] = $user_info['username'];
		} else {
			$data['username'] = '';
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->post['confirm'])) {
			$data['confirm'] = $this->request->post['confirm'];
		} else {
			$data['confirm'] = '';
		}

		if (isset($this->request->post['firstname'])) {
			$data['firstname'] = $this->request->post['firstname'];
		} elseif (!empty($user_info)) {
			$data['firstname'] = $user_info['firstname'];
		} else {
			$data['firstname'] = '';
		}

		if (isset($this->request->post['lastname'])) {
			$data['lastname'] = $this->request->post['lastname'];
		} elseif (!empty($user_info)) {
			$data['lastname'] = $user_info['lastname'];
		} else {
			$data['lastname'] = '';
		}

        if (isset($this->request->post['age'])) {
            $data['age'] = $this->request->post['age'];
        } elseif (!empty($user_info)) {
            $data['age'] = $user_info['age'];
        } else {
            $data['age'] = '';
        }

        if (isset($this->request->post['region'])) {
            $data['region'] = $this->request->post['region'];
        } elseif (!empty($user_info)) {
            $data['region'] = $user_info['region'];
        } else {
            $data['region'] = '';
        }

        if (isset($this->request->post['instagram'])) {
            $data['instagram'] = $this->request->post['instagram'];
        } elseif (!empty($user_info)) {
            $data['instagram'] = $user_info['instagram'];
        } else {
            $data['instagram'] = '';
        }

        if (isset($this->request->post['nation_id'])) {
            $data['nation_id'] = $this->request->post['nation_id'];
        } elseif (!empty($user_info)) {
            $data['nation_id'] = $user_info['nation_id'];
        } else {
            $data['nation_id'] = '';
        }
        if (isset($this->request->post['simple_img'])) {
            $data['simple_img'] = $this->request->post['simple_img'];
        } elseif (!empty($user_info)) {
            $data['simple_img'] = $user_info['simple_img'];
        } else {
            $data['simple_img'] = '';
        }

        if (isset($this->request->post['simple_img']) && is_file(DIR_IMAGE . $this->request->post['simple_img'])) {
            $data['simple_img_thumb'] = $this->model_tool_image->resize($this->request->post['simple_img'], 100, 100);
        } elseif (!empty($user_info) && $user_info['simple_img'] && is_file(DIR_IMAGE . $user_info['simple_img'])) {
            $data['simple_img_thumb'] = $this->model_tool_image->resize($user_info['simple_img'], 100, 100);
        } else {
            $data['simple_img_thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        }

        if (isset($this->request->post['bank_name'])) {
            $data['bank_name'] = $this->request->post['bank_name'];
        } elseif (!empty($user_info)) {
            $data['bank_name'] = $user_info['bank_name'];
        } else {
            $data['bank_name'] = '';
        }
        if (isset($this->request->post['bank_iban'])) {
            $data['bank_iban'] = $this->request->post['bank_iban'];
        } elseif (!empty($user_info)) {
            $data['bank_iban'] = $user_info['bank_iban'];
        } else {
            $data['bank_iban'] = '';
        }
        if (isset($this->request->post['note'])) {
            $data['note'] = $this->request->post['note'];
        } elseif (!empty($user_info)) {
            $data['note'] = $user_info['note'];
        } else {
            $data['note'] = '';
        }

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} elseif (!empty($user_info)) {
			$data['email'] = $user_info['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['desc'])) {
			$data['desc'] = $this->request->post['desc'];
		} elseif (!empty($user_info)) {
			$data['desc'] = $user_info['desc'];
		} else {
			$data['desc'] = '';
		}

		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($user_info)) {
			$data['image'] = $user_info['image'];
		} else {
			$data['image'] = '';
		}

		if (isset($this->request->post['banner'])) {
			$data['banner'] = $this->request->post['image'];
		} elseif (!empty($user_info)) {
			$data['banner'] = $user_info['banner'];
		} else {
			$data['banner'] = '';
		}

		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
		} elseif (!empty($user_info) && $user_info['image'] && is_file(DIR_IMAGE . $user_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($user_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		if (isset($this->request->post['banner']) && is_file(DIR_IMAGE . $this->request->post['banner'])) {
			$data['banner_thumb'] = $this->model_tool_image->resize($this->request->post['banner'], 100, 100);
		} elseif (!empty($user_info) && $user_info['banner'] && is_file(DIR_IMAGE . $user_info['banner'])) {
			$data['banner_thumb'] = $this->model_tool_image->resize($user_info['banner'], 100, 100);
		} else {
			$data['banner_thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('common/profile', $data));
	}

	protected function validateForm() {
		if (!$this->seller->hasPermission('modify', 'common/profile')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		/*if ((utf8_strlen($this->request->post['username']) < 3) || (utf8_strlen($this->request->post['username']) > 20)) {
			$this->error['username'] = $this->language->get('error_username');
		}

		$user_info = $this->model_user_user->getUserByUsername($this->request->post['username']);

		if ($user_info && ($this->seller->getId() != $user_info['user_id'])) {
			$this->error['warning'] = $this->language->get('error_exists_username');
		}*/

		if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
			$this->error['firstname'] = $this->language->get('error_firstname');
		}

		if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
			$this->error['lastname'] = $this->language->get('error_lastname');
		}

		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

        if ((utf8_strlen($this->request->post['age']) < 2)) {
            $this->error['age'] = $this->language->get('error_age');
        }

        if ((utf8_strlen($this->request->post['simple_img']) < 1)) {
           // $this->error['simple_img'] = $this->language->get('error_simple_img');
        }
        if ((utf8_strlen($this->request->post['image']) < 1)) {
            $this->error['image'] = 'الرجاء اضافة الشعار';
        }

        if ((utf8_strlen($this->request->post['bank_name']) < 3)) {
            $this->error['bank_name'] = $this->language->get('error_bank_name');
        }
        if ((utf8_strlen($this->request->post['bank_iban']) < 3)) {
            $this->error['bank_iban'] = $this->language->get('error_bank_iban');
        }

        if ((utf8_strlen($this->request->post['region']) < 3)) {
            $this->error['region'] = $this->language->get('error_region');
        }

        if ((utf8_strlen($this->request->post['nation_id']) != 10)) {
            $this->error['nation_id'] = $this->language->get('error_nation_id');
        }

		$user_info = $this->model_user_user->getUserByEmail($this->request->post['email']);

		if ($user_info && ($this->seller->getId() != $user_info['user_id'])) {
			$this->error['warning'] = $this->language->get('error_exists_email');
		}

		if ($this->request->post['password']) {
			if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
				$this->error['password'] = $this->language->get('error_password');
			}

			if ($this->request->post['password'] != $this->request->post['confirm']) {
				$this->error['confirm'] = $this->language->get('error_confirm');
			}
		}

		return !$this->error;
	}
}
