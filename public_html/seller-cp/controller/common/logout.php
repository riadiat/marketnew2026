<?php
class ControllerCommonLogout extends Controller {
	public function index() {
		$this->seller->logout();

		unset($this->session->data['user_token']);

		$this->response->redirect($this->url->link('common/login', '', true));
	}
}