<?php
class ControllerSellerRegister extends Controller {
    private $error = [];
    public function index(){
        $this->load->language('seller/register');
        $this->load->model('seller/register');
        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->server['HTTPS']) {
            $data['base'] = HTTPS_SERVER;
        } else {
            $data['base'] = HTTP_SERVER;
        }
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_seller_register->register($this->request->post);
            $this->seller->logout();

            unset($this->session->data['user_token']);

            $user_token = token(32);
            $this->session->data['user_token'] = $user_token;
            $this->seller->login($this->request->post['email'], html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8'));


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
            $mail->setSubject('شكرًا لتسجيلك في موقع سوق رياديات');
            $mail->setHtml($this->load->view('seller/new_mail', []));
            $mail->send();

            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $user_token, true));
        }

        if (isset($this->error['first_name'])) {
            $data['error_first_name'] = $this->error['first_name'];
        } else {
            $data['error_first_name'] = '';
        }
        if (isset($this->error['last_name'])) {
            $data['error_last_name'] = $this->error['last_name'];
        } else {
            $data['error_last_name'] = '';
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

        if (isset($this->error['telephone'])) {
            $data['error_telephone'] = $this->error['telephone'];
        } else {
            $data['error_telephone'] = '';
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

        if (isset($this->error['instagram'])) {
            $data['error_instagram'] = $this->error['instagram'];
        } else {
            $data['error_instagram'] = '';
        }
        if (isset($this->error['desc'])) {
            $data['error_desc'] = $this->error['desc'];
        } else {
            $data['error_desc'] = '';
        }

        if (isset($this->error['username'])) {
            $data['error_username'] = $this->error['username'];
        } else {
            $data['error_username'] = '';
        }
        if (isset($this->error['instagram'])) {
            $data['error_instagram'] = $this->error['instagram'];
        } else {
            $data['error_instagram'] = '';
        }

        if (isset($this->error['warning'])) {
            $data['warning'] = $this->error['warning'];
        } else {
            $data['warning'] = '';
        }

        if (isset($this->request->post['first_name'])) {
            $data['first_name'] = $this->request->post['first_name'];
        } else {
            $data['first_name'] = '';
        }

        if (isset($this->request->post['last_name'])) {
            $data['last_name'] = $this->request->post['last_name'];
        } else {
            $data['last_name'] = '';
        }

        if (isset($this->request->post['age'])) {
            $data['age'] = $this->request->post['age'];
        } else {
            $data['age'] = '';
        }

        if (isset($this->request->post['region'])) {
            $data['region'] = $this->request->post['region'];
        } else {
            $data['region'] = '';
        }

        if (isset($this->request->post['instagram'])) {
            $data['instagram'] = $this->request->post['instagram'];
        } else {
            $data['instagram'] = '';
        }

        if (isset($this->request->post['nation_id'])) {
            $data['nation_id'] = $this->request->post['nation_id'];
        } else {
            $data['nation_id'] = '';
        }
        if (isset($this->request->post['simple_img'])) {
            $data['simple_img'] = $this->request->post['simple_img'];
        } else {
            $data['simple_img'] = '';
        }
        if (isset($this->request->post['image'])) {
            $data['image'] = $this->request->post['image'];
        } else {
            $data['image'] = '';
        }
        if (isset($this->request->post['bank_name'])) {
            $data['bank_name'] = $this->request->post['bank_name'];
        } else {
            $data['bank_name'] = '';
        }
        if (isset($this->request->post['bank_iban'])) {
            $data['bank_iban'] = $this->request->post['bank_iban'];
        } else {
            $data['bank_iban'] = '';
        }
        if (isset($this->request->post['note'])) {
            $data['note'] = $this->request->post['note'];
        } else {
            $data['note'] = '';
        }
        if (isset($this->request->post['desc'])) {
            $data['desc'] = $this->request->post['desc'];
        } else {
            $data['desc'] = '';
        }

        if (isset($this->request->post['telephone'])) {
            $data['telephone'] = $this->request->post['telephone'];
        } else {
            $data['telephone'] = '';
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

        if (isset($this->request->post['email'])) {
            $data['email'] = $this->request->post['email'];
        } else {
            $data['email'] = '';
        }

        if (isset($this->request->post['username'])) {
            $data['username'] = $this->request->post['username'];
        } else {
            $data['username'] = '';
        }

        $data['url'] = HTTPS_CATALOG;

        $data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

        $data['description'] = $this->document->getDescription();
        $data['keywords'] = $this->document->getKeywords();
        $data['links'] = $this->document->getLinks();
        $data['styles'] = $this->document->getStyles();
        $data['scripts'] = $this->document->getScripts();
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');

        $data['login_link'] = $this->url->link('common/login', '', true);

        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');

        $data['login'] = $this->url->link('common/login', '' ,true);

        $this->load->model('catalog/information');

        $information_info = $this->model_catalog_information->getInformation(10);

        $info_agree = $this->model_catalog_information->getInformationDescriptions(10);
        $title = 'الشروط والأحكام';

        $data['agree_content'] = html_entity_decode(@$info_agree[2]['description'], ENT_QUOTES, 'UTF-8');
        $data['agree_title'] = $title;

        $data['text_agree'] = sprintf($this->language->get('text_agree'), '#agree', $title);


        $this->response->setOutput($this->load->view('seller/register', $data));
    }

    protected function validate() {
        $this->load->model('seller/register');
        if ((utf8_strlen(trim($this->request->post['first_name'])) < 3) || (utf8_strlen(trim($this->request->post['first_name'])) > 32)) {
            $this->error['first_name'] = $this->language->get('error_firstname');
        }
        if ((utf8_strlen(trim($this->request->post['last_name'])) < 3) || (utf8_strlen(trim($this->request->post['last_name'])) > 32)) {
            $this->error['last_name'] = $this->language->get('error_lastname');
        }
        if ((utf8_strlen($this->request->post['age']) < 2)) {
            $this->error['age'] = $this->language->get('error_age');
        }

        if ((utf8_strlen($this->request->post['simple_img']) < 1)) {
            //$this->error['simple_img'] = $this->language->get('error_simple_img');
        }
        if ((utf8_strlen($this->request->post['image']) < 1)) {
            $this->error['image'] = $this->language->get('error_image');
        }
        if ((utf8_strlen($this->request->post['instagram']) < 1)) {
            $this->error['instagram'] = $this->language->get('error_instagram');
        }

        if ((utf8_strlen($this->request->post['bank_name']) < 3)) {
            $this->error['bank_name'] = $this->language->get('error_bank_name');
        }
        if ((utf8_strlen($this->request->post['desc']) < 3)) {
            $this->error['desc'] = $this->language->get('error_description');
        }
        if ((utf8_strlen($this->request->post['bank_iban']) < 3)) {
            $this->error['bank_iban'] = $this->language->get('error_bank_iban');
        }

        if ((utf8_strlen($this->request->post['region']) < 3)) {
            $this->error['region'] = $this->language->get('error_region');
        }

        // if ((utf8_strlen($this->request->post['nation_id']) != 10)) {
        if ((utf8_strlen($this->request->post['nation_id']) < 3)) {
            $this->error['nation_id'] = $this->language->get('error_nation_id');
        }

        if ((utf8_strlen($this->request->post['telephone']) != 10)) {
            $this->error['telephone'] = $this->language->get('error_telephone');
        }else{
            $is_telephone = $this->seller->getSellerByTelephone($this->request->post['telephone']);
            if($is_telephone){
                $this->error['telephone'] = 'رقم الجوال مستخدم مسبقاً';
            }
        }

        if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error['email'] = $this->language->get('error_email');
        }else{
            $is_email = $this->seller->getSellerByEmail($this->request->post['email']);
            if($is_email){
                $this->error['email'] = 'البريد الإلكتروني مستخدم مسبقاً';
            }
        }

        if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
            $this->error['password'] = $this->language->get('error_password');
        }
        if ($this->request->post['confirm'] != $this->request->post['password']) {
            $this->error['confirm'] = $this->language->get('error_confirm');
        }
        if (utf8_strlen(strip_tags($this->request->post['username'])) < 3) {
            $this->error['username'] = $this->language->get('error_url_1');
        }elseif(filter_var(strip_tags($this->request->post['username']), FILTER_VALIDATE_URL)){
            $this->error['username'] = $this->language->get('error_url_3');
        }elseif (preg_match('/[^A-Za-z]/', strip_tags($this->request->post['username']))){
            $this->error['username'] = $this->language->get('error_url_2');
        }else{
            $getUser = $this->model_seller_register->getSellerByUsername($this->request->post['username']);
            if($getUser){
                $this->error['username'] = $this->language->get('error_url_4');
            }
        }


        if (!isset($this->request->post['agree'])) {
            $this->error['warning'] = $this->language->get('error_agree');
        }

        return !$this->error;
    }
}
