<?php
class ControllerExtensionPaymentAdvancedBankTransfer extends Controller {
	public function index() {
		$this->load->language('extension/payment/advanced_bank_transfer');

		$data['bank'] = nl2br($this->config->get('payment_advanced_bank_transfer_bank' . $this->config->get('config_language_id')));

        $data['advanced_bank_transfer_form'] = $this->load->controller('extension/payment/advanced_bank_transfer/form');

		return $this->load->view('extension/payment/advanced_bank_transfer', $data);
	}

	public function confirm() {
        $this->load->language('extension/payment/advanced_bank_transfer');
		$json = array();
		$json['success'] = false;
		$json['post'] = $this->request->post;
		$json['payment_method'] = @$this->session->data['payment_method'];
		$errors = [];

        if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 32)) {
            $errors['name'] = $this->language->get('error_name');
        }
        if (empty($this->request->post['bank']) ) {
            $errors['bank'] = $this->language->get('error_bank');
        }
        if (empty($this->request->post['amount'])) {
            $errors['amount'] = $this->language->get('error_amount');
        }
        if (empty($this->request->post['image'])) {
            $errors['image'] = $this->language->get('error_image');
        }
        if (isset($this->request->post['order_id']) && empty($this->request->post['order_id'])) {
            $errors['order_id'] = $this->language->get('error_order_id');
        }

		if(!empty($errors)){
            $json['errors'] = $errors;
        }else{
            $json['success'] = true;
            $this->load->model('checkout/order');

            if(isset($this->request->post['order_id'])){
                $order_id = (int)$this->arabicToEnglish($this->request->post['order_id']);
            }else{
                $order_id = $this->session->data['order_id'];
            }

            $comment  = $this->language->get('entry_bank_name') . " : ".strip_tags($this->request->post['bank'])."\n\n";
            $comment .= $this->language->get('entry_name') . " : ".strip_tags($this->request->post['name'])."\n\n";
            $comment .= $this->language->get('entry_amount') . " : ".strip_tags($this->request->post['amount'])."\n\n";
            $comment .= $this->language->get('entry_image') . " : <a target='blank' href='".$this->url->link('extension/payment/advanced_bank_transfer/view','code='.strip_tags($this->request->post['image']))."'>".$this->language->get('entry_click_here')."</a>"."\n\n";

            if($this->request->post['comment']){
                $comment .= $this->language->get('entry_comment') . " : ".nl2br($this->request->post['comment'])."\n\n";
            }

            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_advanced_bank_transfer_order_status_id_success'), $comment, true);
            $json['redirect'] = $this->url->link('checkout/success');
        }
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}

	public function skip() {
		$json = array();

        $this->load->language('extension/payment/bank_transfer');

        $this->load->model('checkout/order');

        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_advanced_bank_transfer_order_status_id_skip'), '', true);

        $json['redirect'] = $this->url->link('checkout/success');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function form(){
        $this->load->model('tool/image');
        $data['action_confirm'] = $this->url->link('extension/payment/advanced_bank_transfer/confirm');
        $banks = $this->getBanks();
        $data['banks'] = [];
        if(isset($this->request->get['is_order'])){
            $data['is_order'] = true;
        }else{
            $data['is_order'] = false;
        }
        foreach ($banks as $bank){
            $data['banks'][] = [
                'id'=>$bank['id'],
                'name'=>$bank['name'],
                'number'=>$bank['number'],
                'iban'=>$bank['iban'],
                'image'=>$this->model_tool_image->resize($bank['image'], 80, 80),
            ];
        }
        return $this->load->view('extension/payment/advanced_bank_transfer_form', $data);
    }
    private function getBanks(){
	    $query = $this->db->query("SELECT * FROM `".DB_PREFIX."bank_list`");
	    return $query->rows;
    }
    public function view() {
        $this->load->model('tool/upload');

        if (isset($this->request->get['code'])) {
            $code = $this->request->get['code'];
        } else {
            $code = 0;
        }

        $upload_info = $this->model_tool_upload->getUploadByCode($code);

        if ($upload_info) {
            $file = DIR_UPLOAD . $upload_info['filename'];
            if (!headers_sent()) {
                if (is_file($file)) {
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file));
                    header('Content-type: jpg');

                    readfile($file, 'rb');
                    exit;
                } else {
                    exit('Error: Could not find file ' . $file . '!');
                }
            } else {
                exit('Error: Headers already sent out!');
            }
        }
    }

    private function toUTF8($number){
        return mb_convert_encoding('&#x' . $number . ';', 'UTF-8', 'HTML-ENTITIES');
    }

    private function arabicToEnglish($data){
        $englishNumbers = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $arabicNumbers = ['0660', '0661', '0662', '0663', '0664', '0665', '0666', '0667', '0668', '0669'];
        $arabicNumbersConverated = array_map([$this, 'toUTF8'], $arabicNumbers);
        return str_replace($arabicNumbersConverated, $englishNumbers, $data);
    }
}