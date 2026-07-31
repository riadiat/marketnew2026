<?php
require_once DIR_SYSTEM . '/library/payfortFort/init.php';
class ControllerExtensionPaymentApplePay extends Controller
{
    private $production_certificate_key;
    private $production_certificate_path;
    private $production_domainname;
    // private $production_certificate_key_pass = 'Lodore@123';
    private $production_certificate_key_pass = '123456';
    private $file;
    private $production_merchantidentifier;
    private $domain = 'ewmarket.sa';
    private $access_code = '1gpaMOAplQKpbkelpNIR';

    public $paymentMethod;
    public $integrationType;
    public $pfConfig;
    public $pfPayment;
    public $pfHelper;
    public $pfOrder;

    public function __construct($registry)
    {
        parent::__construct($registry);
        // $this->production_certificate_key = DIR_STORAGE . 'cer/ApplePay.key.pem';
        // $this->production_certificate_path = DIR_STORAGE . 'cer/ApplePay.crt.pem';
        $this->production_certificate_key = DIR_STORAGE . 'new_cer/ApplePay.key.pem';
        $this->production_certificate_path = DIR_STORAGE . 'new_cer/ApplePay.crt.pem';
        $file_uid = openssl_x509_parse(file_get_contents($this->production_certificate_path));
        $this->production_merchantidentifier = $file_uid['subject']['UID'];
        $this->file = $file_uid;
        $this->production_domainname = $this->config->get('config_name');

        $this->pfConfig        = Payfort_Fort_Config::getInstance($registry);
        $this->pfPayment       = Payfort_Fort_Payment::getInstance($registry);
        $this->pfHelper        = Payfort_Fort_Helper::getInstance($registry);
        $this->pfOrder         = new Payfort_Fort_Order($registry);
        $this->integrationType = $this->pfConfig->getCcIntegrationType();
        $this->paymentMethod   = PAYFORT_FORT_PAYMENT_METHOD_CC;
    }

    public function index()
    {
        $this->load->model('checkout/order');
        $this->load->model('localisation/country');
        $this->load->language('extension/payment/apple_pay');
        //$order_id = $this->session->data['order_id'];
        $order_id = $this->gerOrderId();
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $getOrderTotals = $this->model_checkout_order->getOrderTotals($order_info['order_id']);
        $getOrderProducts = $this->model_checkout_order->getOrderProducts($order_info['order_id']);
        $data['production_merchantidentifier'] = $this->production_merchantidentifier;
        $data['order_id'] = $order_info['order_id'];
        $countryCode = $this->model_localisation_country->getCountry($order_info['payment_country_id']);
        $data['countryCode'] = $countryCode['iso_code_2'];
        $data['callback_url'] = $this->url->link('extension/payment/apple_pay/callback', '', true);
        $data['currency_code'] = $order_info['currency_code'];
        $data['order_info'] = $order_info;
        $data['shipping'] = $this->getVArray($getOrderTotals, 'shipping');
        $data['shipping']['value'] = round($data['shipping']['value'] , 2 );
        $data['sub_total'] = $this->getVArray($getOrderTotals, 'sub_total');;
        $data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $data['description'] = 'Order id #' . $order_id;
        $data['lineItems'] = $this->renderLineItems($getOrderTotals, $order_info);

        $testMode = $this->config->get('payment_payfort_fort_entry_sandbox_mode');
        $data['gatewayUrl'] = $testMode ? 'https://sbpaymentservices.payfort.com/FortAPI/paymentApi' : 'https://paymentservices.payfort.com/FortAPI/paymentApi';

        return $this->load->view('extension/payment/apple_pay', $data);
    }

    public function confirm()
    {

        $json = array();
        $this->load->model('checkout/order');
        $this->load->model('localisation/country');
        $post = $this->request->post;

        $order_id = $this->gerOrderId();
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $testMode = $this->getSettings('testMode');
        $gatewayUrl = $testMode ? 'https://sbpaymentservices.payfort.com/FortAPI/paymentApi' : 'https://paymentservices.payfort.com/FortAPI/paymentApi';


        $data  = array(
            'command'            =>'PURCHASE',
            'access_code'        =>$this->access_code,
            'merchant_reference'=>$this->merchant_reference($order_id),
            'merchant_identifier' =>$this->getSettings('merchant_identifier'),
            'amount' => (string)$this->convertFortAmount($order_info['total'], $order_info['currency_value'], $order_info['currency_code']),
            'currency' => $order_info['currency_code'],
            'language' => $this->language->get('code'),
            'customer_email'     =>$order_info['email'],
            'customer_name'     =>$order_info['firstname'],
            'return_url'  =>$this->url->link('extension/payment/apple_pay/feedback', '', true),
        );
        $data['digital_wallet'] = 'APPLE_PAY';
        $data['apple_data'] = $post['paymentData']['data'];
        $data['apple_signature'] = $post['paymentData']['signature'];
        $data['apple_header'] = [
            'apple_transactionId'=>$post['paymentData']['header']['transactionId'],
            'apple_publicKeyHash'=>$post['paymentData']['header']['publicKeyHash'],
            'apple_ephemeralPublicKey'=>$post['paymentData']['header']['ephemeralPublicKey'],
        ];
        $data['apple_paymentMethod'] = [
            'apple_displayName'=>$post['paymentMethod']['displayName'],
            'apple_network'=>$post['paymentMethod']['network'],
            'apple_type'=>$post['paymentMethod']['type'],
        ];


        $shaString = $this->getSettings('request_sha_phrase') . $this->arrayToString($data) . $this->getSettings('request_sha_phrase');

        $signature = hash('sha256', $shaString);
        $data['signature'] = $signature;

        $this->saveAppleData($order_id,json_encode($data));

        $response = $this->callApi($data, $gatewayUrl);
        
        if(isset($response['3ds_url'])){
            $redirect = $response['3ds_url'];
        }else{
            if($response['response_code'] =='14000' || $response['response_code'] ==14000){
                $sh = $response;
                unset($sh['signature']);
                $shaString = $this->getSettings('response_sha_phrase') . $this->arrayToString($sh) . $this->getSettings('response_sha_phrase');
                $signature = hash('sha256', $shaString);
                if($signature ==$response['signature']){
                    $this->model_checkout_order->addOrderHistory($order_info['order_id'],$this->getSettings('order_status_id'),'Paid: '.$order_info['order_id']);
                    $redirect = $this->url->link('extension/payment/payfort_fort/success');
                }else{
                    $this->model_checkout_order->addOrderHistory($order_info['order_id'],10,'');
                    $redirect = $this->url->link('checkout/failure');
                }
            }else{
                $this->model_checkout_order->addOrderHistory($order_info['order_id'],10,'');
                $redirect = $this->url->link('checkout/failure');
            }
        }
        if(isset($response['response_message'])){
            $this->session->data['error'] = $response['response_message'];
        }
        $json['redirect'] = $redirect;


        $this->saveDb(json_encode($data),json_encode($response),$shaString);

        $this->response->addHeader('Access-Control-Allow-Origin : *');
        $this->response->addHeader('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
        $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With, Application');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function feedback(){
        $this->load->model('checkout/order');
        $response_params = array_merge($this->request->get, $this->request->post);
        if($response_params['response_code'] =='14000' || $response_params['response_code'] ==14000){
            $sh = $response_params;
            unset($sh['signature']);
            $shaString = $this->getSettings('response_sha_phrase') . $this->arrayToString($sh) . $this->getSettings('response_sha_phrase');
            $signature = hash('sha256', $shaString);
            if($signature ==$response_params['signature']){
                $redirect = $this->url->link('extension/payment/payfort_fort/success');
            }else{
                $redirect = $this->url->link('checkout/failure');
            }
        }else{
            $redirect = $this->url->link('checkout/failure');
        }

        echo '<script>window.top.location.href = "' . $redirect . '"</script>';
        exit;
    }

    public function validPayment()
    {
        $json = [];
        $ch = curl_init();
        $validation_url = $this->request->get['u'];
        $json['success'] = false;

        if ("https" == parse_url($validation_url, PHP_URL_SCHEME) && substr(parse_url($validation_url, PHP_URL_HOST), -10) == ".apple.com") {
            $data = '{"merchantIdentifier":"' . $this->production_merchantidentifier . '", "domainName":"' . $this->domain . '", "displayName":"' . $this->production_domainname . '"}';

            curl_setopt($ch, CURLOPT_URL, $validation_url);
            curl_setopt($ch, CURLOPT_SSLCERT, $this->production_certificate_path);
            curl_setopt($ch, CURLOPT_SSLKEY, $this->production_certificate_key);
            curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->production_certificate_key_pass);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);

            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            if ($result === false) {
                $json['error'] = curl_error($ch);
            } else {
                $response = $result;
                $json['success'] = true;
                $json['response'] = json_decode($response);
            }
            // close cURL resource, and free up system resources
            curl_close($ch);
        } else {
            $json['error'] = 'not valid';
        }
        $this->saveDb(json_encode($json));
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    private function getVArray($items, $search)
    {
        foreach ($items as $item) {
            if (isset($item['code']) && $item['code'] == $search) {
                return $item;
            }
        }
    }

    private function renderLineItems($totals, $order_info)
    {
        $output = [];
        foreach ($totals as $total) {

            $title = strip_tags($total['title']);
            $total['label'] = preg_replace('/[0-9]+/', '', $title);;
            $total['amount'] = (string)$this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false);
            $total['type'] = 'final';
            unset($total['sort_order']);
            unset($total['order_total_id']);
            unset($total['order_id']);
            unset($total['title']);
            unset($total['value']);
            unset($total['code']);
            $output [] = $total;
        }
        return json_encode($output);
    }

    private function saveDb($data, $response = '',$string = '')
    {
        $this->db->query("INSERT INTO oc_apple_pay SET  `data` = '" . $this->db->escape($data) . "' , `response`='" . $this->db->escape($response) . "', `string`='" . $this->db->escape($string) . "'");
    }
    private function saveAppleData($order_id,$data){
        if($this->getAppleData($order_id)){
            $this->db->query("UPDATE oc_apple_data SET  `data` = '" . $this->db->escape($data) . "'  WHERE `order_id`='" . (int)$order_id . "'");
        }else{
            $this->db->query("INSERT INTO oc_apple_data SET  `data` = '" . $this->db->escape($data) . "' , `order_id`='" . (int)$order_id . "'");
        }
    }
    private function getAppleData($order_id){
        $query = $this->db->query("SELECT * FROM oc_apple_data WHERE `order_id`='" . (int)$order_id . "'");
        return $query->row;
    }
    private function getSettings($key)
    {
        $output = [
            'testMode' => $this->config->get('payment_payfort_fort_entry_sandbox_mode'),
            'command' => $this->config->get('payment_payfort_fort_entry_command'),
            'access_code' => $this->config->get('payment_payfort_fort_entry_access_code'),
            'merchant_identifier' => $this->config->get('payment_payfort_fort_entry_merchant_identifier'),
            'hash_algorithm' => $this->config->get('payment_payfort_fort_entry_hash_algorithm'),
            'request_sha_phrase' => $this->config->get('payment_payfort_fort_entry_request_sha_phrase'),
            'response_sha_phrase' => $this->config->get('payment_payfort_fort_entry_response_sha_phrase'),
            'order_status_id' => $this->config->get('payment_payfort_fort_order_status_id'),
        ];
        if (isset($output[$key])) {
            return $output[$key];
        }
        return false;
    }

    private function convertFortAmount($amount, $currency_value, $currency_code)
    {
        $gateway_currency = 'base';
        $decimal_points = $this->getCurrencyDecimalPoints($currency_code);
        if ($gateway_currency == 'front') {
            $new_amount = round($amount * $currency_value, $decimal_points);
        } else {
            $new_amount = round($amount, $decimal_points);
        }
        $new_amount = $new_amount * (pow(10, $decimal_points));
        return $new_amount;
    }

    private function getCurrencyDecimalPoints($currency)
    {
        $decimalPoint = 2;
        $arrCurrencies = array(
            'JOD' => 3,
            'KWD' => 3,
            'OMR' => 3,
            'TND' => 3,
            'BHD' => 3,
            'LYD' => 3,
            'IQD' => 3,
        );
        if (isset($arrCurrencies[$currency])) {
            $decimalPoint = $arrCurrencies[$currency];
        }
        return $decimalPoint;
    }

    private function callApi($postData, $gatewayUrl)
    {
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0";
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json;charset=UTF-8',
            //'Accept: application/json, application/*+json',
            //'Connection:keep-alive'
        ));
        curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_ENCODING, "compress, gzip");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); // The number of seconds to wait while trying to connect
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $response = curl_exec($ch);

        curl_close($ch);

        $array_result = json_decode($response, true);

        if (!$response || empty($array_result)) {
            return false;
        }
        return $array_result;
    }
    private function merchant_reference($order_id){
        return $order_id;
    }
    private function gerOrderId(){
        return $this->session->data['order_id'];
    }
    private function arrayToString($data){
        $shaString = '';
        ksort($data);
        foreach ($data as $key => $value) {
            if(is_array($value)){
                $v = '{';
                $sep = ', ';
                $x = 0;
                $count = count($value);
                foreach ($value as $k =>$rv){
                    $x++;

                    if($count == $x){
                        $sep = null;
                    }
                    $v .= "$k=$rv".$sep;
                }
                $v .= '}';
                $value = $v;
            }
            $shaString .= "$key=$value";
        }
        return $shaString;
    }
}
