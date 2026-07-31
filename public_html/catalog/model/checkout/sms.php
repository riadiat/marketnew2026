<?php
class ModelCheckoutSms extends Model {

    private function addSms($data){
        $this->db->query("INSERT INTO " . DB_PREFIX . "sms SET mobile='".$this->db->escape($data['mobile'])."', msg='".$this->db->escape($data['msg'])."',result='".$this->db->escape($data['result'])."'");
    }

    public function send_sms($order_id,$order_status_id,$comment){
        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($order_id);


        $this->load->model('account/order');
        $numbers =  "966" . $this->arabicToEnglish(substr($order['telephone'], -9));

        $msg = false;
        $total = $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);

        if($order_status_id==1 || $order_status_id == 2){
            $msg ='تم استلام الطلب رقم #'.$order['order_id']." بإجمالي قيمة ".$total.' شكرًا لتسوقكم من متجر Vancyes';
        }

        if($order_status_id==6){
            $s = ". نود أن نعلمك أنه قد تم شحن منتجاتك طلب رقم #".$order_id." لدى Vancyes"." \n ";
            $u = '';
            if(!empty($order['tracking'])){
                $u = 'http://www.sls-express.com/tr/'.$order['tracking'];
            }else{
                if(!empty($comment)){
                    $u = $comment;
                }
            }
            $msg =$s.$u;
        }

        /**/
        /*if($order_status_id==2){
            if(!empty($comment)){
                $msg =  $comment;
            }else{
                $msg ='لقد تم استلام الطلب رقم # '.$order['order_id'].' بنجاح، بإجمالي قيمة '.$total.' ر.س شكرًا لتسوقكم من متجر سكدج';
            }
        }*/
       /* if($order_status_id==18){
            if(!empty($comment)){
                $msg =  $comment;
            }else{
                $msg ='تم استلام الطلب رقم #'.$order['order_id']." بإجمالي قيمة ".$total.' بنجاح ، سيتم التواصل معكم لتأكيد الطلب ، شكرًا لتسوقكم من متجر سكدج';
            }
        }*/




       /* if($order_status_id==17){
            if(!empty($comment)){
                $msg =  $comment;
            }
        }*/

        if($msg){
            $this->setting_sms($numbers,$msg);
        }



    }
    public function sendSms($msg,$mobile){
        //$mobile =  $this->arabicToEnglish($mobile);
        $mobile =  "966" . $this->arabicToEnglish(substr($mobile, -9));
        $this->setting_sms($mobile,$msg);
    }

    private function setting_sms($mobile,$msg){
        $data = array(
            "msg" => $msg,
            "to" => $mobile,
        );
        $data_string = json_encode($data);

        $ch = curl_init('http://api.unifonic.com/wrapper/sendSMS.php?userid=info@vancyes.com&password=Az0580020075&sender=vancyes&encoding=UTF8&');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $result = curl_exec($ch);
        $data_w = array(
            'mobile'=>$mobile,
            'msg'=>$msg,
            'result'=>$result,
        );
        $this->addSms($data_w);
        return $result;
    }
    private function toUTF8($number){
        return mb_convert_encoding('&#x' . $number . ';', 'UTF-8', 'HTML-ENTITIES');
    }

    public function arabicToEnglish($data){
        $englishNumbers = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $arabicNumbers = ['0660', '0661', '0662', '0663', '0664', '0665', '0666', '0667', '0668', '0669'];
        $arabicNumbersConverated = array_map([$this, 'toUTF8'], $arabicNumbers);
        return str_replace($arabicNumbersConverated, $englishNumbers, $data);
    }
}
