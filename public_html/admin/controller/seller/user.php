<?php
require_once DIR_SYSTEM.'library/box/spout/src/Spout/Autoloader/autoload.php';
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;
class ControllerSellerUser extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('seller/user');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('seller/user');

		$this->getList();
	}

	public function add() {
		$this->load->language('seller/user');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('seller/user');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_seller_user->addUser($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('seller/user');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('seller/user');
		$getUser =$this->model_seller_user->getUser($this->request->get['user_id']);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_seller_user->editUser($this->request->get['user_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if($getUser['status']==0 && $this->request->post['status'] ==1){
                $mail = new Mail($this->config->get('config_mail_engine'));
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

                $mail->setTo($this->request->post['email']);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject('لقد تم قبول تسجيلكم في منصة سوق رياديات');
                $mail->addAttachment(DIR_IMAGE.'intro.pdf');
                $mail->setHtml($this->load->view('seller/active_mail', []));
                $mail->send();
            }

			$this->response->redirect($this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('seller/user');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('seller/user');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $user_id) {
				$this->model_seller_user->deleteUser($user_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'date_added';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        if (isset($this->request->get['filter_username'])) {
            $filter_username = $this->request->get['filter_username'];
        } else {
            $filter_username = '';
        }

        if (isset($this->request->get['filter_name_name'])) {
            $filter_name_name = $this->request->get['filter_name_name'];
        } else {
            $filter_name_name = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        $url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

        if (isset($this->request->get['filter_username'])) {
            $url .= '&filter_username=' . urlencode(html_entity_decode($this->request->get['filter_username'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_name_name'])) {
            $url .= '&filter_name_name=' . urlencode(html_entity_decode($this->request->get['filter_name_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('seller/user/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('seller/user/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['export'] = $this->url->link('seller/user/export', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['users'] = array();

		$filter_data = array(
            'filter_name'    => $filter_username,
            'filter_name_name'     => $filter_name_name,
            'filter_status'     => $filter_status,
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$user_total = $this->model_seller_user->getTotalUsers($filter_data);

		$results = $this->model_seller_user->getUsers($filter_data);

		foreach ($results as $result) {
			$data['users'][] = array(
				'user_id'    => $result['user_id'],
				'username'   => $result['username'],
				'name'   => $result['firstname'].' '.$result['lastname'],
				'status'     => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'edit'       => $this->url->link('seller/user/edit', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $result['user_id'] . $url, true)
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_username'] = $this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . '&sort=username' . $url, true);
		$data['sort_status'] = $this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);
		$data['sort_date_added'] = $this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . '&sort=date_added' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}
        if (isset($this->request->get['filter_username'])) {
            $url .= '&filter_username=' . urlencode(html_entity_decode($this->request->get['filter_username'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_name_name'])) {
            $url .= '&filter_name_name=' . urlencode(html_entity_decode($this->request->get['filter_name_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

		$pagination = new Pagination();
		$pagination->total = $user_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($user_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($user_total - $this->config->get('config_limit_admin'))) ? $user_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $user_total, ceil($user_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['user_token'] = $this->session->data['user_token'];

        $data['filter_name_name'] = $filter_name_name;
        $data['filter_username'] = $filter_username;
        $data['filter_status'] = $filter_status;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('seller/user_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['user_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

		if (isset($this->error['price'])) {
			$data['error_price'] = $this->error['price'];
		} else {
			$data['error_price'] = '';
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

		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = '';
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

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['user_id'])) {
			$data['action'] = $this->url->link('seller/user/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('seller/user/edit', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $this->request->get['user_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('seller/user', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['user_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$user_info = $this->model_seller_user->getUser($this->request->get['user_id']);
		}

		if (isset($this->request->post['username'])) {
			$data['username'] = $this->request->post['username'];
		} elseif (!empty($user_info)) {
			$data['username'] = $user_info['username'];
		} else {
			$data['username'] = '';
		}

		if (isset($this->request->post['user_group_id'])) {
			$data['user_group_id'] = $this->request->post['user_group_id'];
		} elseif (!empty($user_info)) {
			$data['user_group_id'] = $user_info['user_group_id'];
		} else {
			$data['user_group_id'] = '';
		}

		$this->load->model('seller/user_group');

		$data['user_groups'] = $this->model_seller_user_group->getUserGroups();

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

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} elseif (!empty($user_info)) {
			$data['email'] = $user_info['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($user_info)) {
			$data['image'] = $user_info['image'];
		} else {
			$data['image'] = '';
		}

		if (isset($this->request->post['banner'])) {
			$data['banner'] = $this->request->post['banner'];
		} elseif (!empty($user_info)) {
			$data['banner'] = $user_info['banner'];
		} else {
			$data['banner'] = '';
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} elseif (!empty($user_info)) {
			$data['telephone'] = $user_info['telephone'];
		} else {
			$data['telephone'] = '';
		}

		$this->load->model('tool/image');

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

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($user_info)) {
			$data['status'] = $user_info['status'];
		} else {
			$data['status'] = 0;
		}

		if (isset($this->request->post['show_home'])) {
			$data['show_home'] = $this->request->post['show_home'];
		} elseif (!empty($user_info)) {
			$data['show_home'] = $user_info['show_home'];
		} else {
			$data['show_home'] = 0;
		}

        if (isset($this->request->post['price'])) {
            $data['price'] = $this->request->post['price'];
        } elseif (!empty($user_info)) {
            $data['price'] = $user_info['price'];
        } else {
            $data['price'] = '';
        }

        if (isset($this->request->post['prefix_price'])) {
            $data['prefix_price'] = $this->request->post['prefix_price'];
        } elseif (!empty($user_info)) {
            $data['prefix_price'] = $user_info['prefix_price'];
        } else {
            $data['prefix_price'] = '';
        }

        if (isset($this->request->post['desc'])) {
            $data['desc'] = $this->request->post['desc'];
        } elseif (!empty($user_info)) {
            $data['desc'] = $user_info['desc'];
        } else {
            $data['desc'] = '';
        }

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('seller/user_form', $data));
	}

	protected function validateForm() {
        $this->load->model('seller/user');
		if (!$this->user->hasPermission('modify', 'seller/user')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

        if (utf8_strlen(strip_tags($this->request->post['username'])) < 3) {
            $this->error['username'] = $this->language->get('error_url_1');
        }elseif(filter_var(strip_tags($this->request->post['username']), FILTER_VALIDATE_URL)){
            $this->error['username'] = $this->language->get('error_url_3');
        }elseif (preg_match('/[^A-Za-z]/', strip_tags($this->request->post['username']))){
            $this->error['username'] = $this->language->get('error_url_2');
        }else{
            $getUser = $this->model_seller_user->getUserByUsername($this->request->post['username']);
            if($getUser){
                if(isset($this->request->get['user_id'])){
                    if($getUser['user_id'] != $this->request->get['user_id']){
                        $this->error['username'] = $this->language->get('error_url_4');
                    }
                }

            }

        }

		$user_info = $this->model_seller_user->getUserByUsername($this->request->post['username']);

		if (!isset($this->request->get['user_id'])) {
			if ($user_info) {
				$this->error['warning'] = $this->language->get('error_exists_username');
			}
		} else {
			if ($user_info && ($this->request->get['user_id'] != $user_info['user_id'])) {
				$this->error['warning'] = $this->language->get('error_exists_username');
			}
		}

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
            //$this->error['simple_img'] = $this->language->get('error_simple_img');
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

        // if ((utf8_strlen($this->request->post['nation_id']) != 10)) {
        if ((utf8_strlen($this->request->post['nation_id']) < 3 )) {
            $this->error['nation_id'] = $this->language->get('error_nation_id');
        }

		if (empty($this->request->post['price'])) {
			$this->error['price'] = $this->language->get('error_price');
		}else{
		    if(floatval($this->request->post['price']) < 1){
                $this->error['price'] = 'يجب ان تكون القيمة اكبر من 0';
            }
        }

		if (empty($this->request->post['telephone'])) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		$user_info = $this->model_seller_user->getUserByEmail($this->request->post['email']);

		if (!isset($this->request->get['user_id'])) {
			if ($user_info) {
				$this->error['warning'] = $this->language->get('error_exists_email');
			}
		} else {
			if ($user_info && ($this->request->get['user_id'] != $user_info['user_id'])) {
				$this->error['warning'] = $this->language->get('error_exists_email');
			}
		}

		if ($this->request->post['password'] || (!isset($this->request->get['user_id']))) {
			if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
				$this->error['password'] = $this->language->get('error_password');
			}

			if ($this->request->post['password'] != $this->request->post['confirm']) {
				$this->error['confirm'] = $this->language->get('error_confirm');
			}
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'seller/user')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['selected'] as $user_id) {
			if ($this->user->getId() == $user_id) {
				$this->error['warning'] = $this->language->get('error_account');
			}
		}

		return !$this->error;
	}

    public function autocomplete() {
        $this->response->addHeader('Content-Type: application/json');
        $json = array();
        $this->load->model('seller/user');
        $filter_name = null;
        $filter_name_name = null;
        $filter_lastname = null;
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        }
        if (isset($this->request->get['filter_username'])) {
            $filter_name = $this->request->get['filter_username'];
        }
        if (isset($this->request->get['filter_name_name'])) {
            $filter_name_name = $this->request->get['filter_name_name'];
        }
        if($filter_name_name == null || $filter_name == null){
            $this->response->setOutput(json_encode($json));
        }

        $filter_data = array(
            'filter_name_name' => $filter_name_name,
            'filter_name' => $filter_name,
            'start'       => 0,
            'limit'       => 5
        );

        $results = $this->model_seller_user->getUsers($filter_data);

        foreach ($results as $result) {
            $json[] = array(
                'seller_id' => $result['user_id'],
                'name' => $result['firstname'].' '.$result['lastname'],
                'username'            => strip_tags(html_entity_decode($result['username'], ENT_QUOTES, 'UTF-8'))
            );
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['username'];
        }

        array_multisort($sort_order, SORT_ASC, $json);


        $this->response->setOutput(json_encode($json));
    }

    public function export(){
	    $sellers = $this->db->query("SELECT * FROM oc_seller");
        $sellers = $sellers->rows;
        $filePath = DIR_STORAGE.'xls/sellers.xlsx';

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToBrowser($filePath);

        foreach ($sellers as $seller){
            $data = [
                WriterEntityFactory::createCell($seller['user_id']),
                WriterEntityFactory::createCell($seller['firstname']. ' '.$seller['lastname']),
                WriterEntityFactory::createCell($seller['username']),
                WriterEntityFactory::createCell($seller['age']),
                WriterEntityFactory::createCell($seller['instagram']),
                WriterEntityFactory::createCell($seller['region']),
                WriterEntityFactory::createCell($seller['bank_name']),
                WriterEntityFactory::createCell($seller['bank_iban']),
                WriterEntityFactory::createCell($seller['nation_id']),
                WriterEntityFactory::createCell($seller['telephone']),
                WriterEntityFactory::createCell($seller['email']),
                WriterEntityFactory::createCell(strip_tags(html_entity_decode($seller['note'], ENT_QUOTES, 'UTF-8'))),
            ];

            $item = WriterEntityFactory::createRow($data);
            $writer->addRow($item);
        }
        $writer->close();
    }
}
