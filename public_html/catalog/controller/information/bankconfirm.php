<?php
class ControllerInformationBankConfirm extends Controller {
    public function index() {
        if(!isset($this->request->get['is_order'])){
            $this->response->redirect($this->url->link('common/home', '', true));
        }

        $this->load->language('extension/payment/advanced_bank_transfer');

        $this->load->model('catalog/information');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('information/contact')
        );

        $data['heading_title'] = $this->language->get('heading_title');

        $data['advanced_bank_transfer_form'] = $this->load->controller('extension/payment/advanced_bank_transfer/form');

        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('information/bankconfirm', $data));

    }
}