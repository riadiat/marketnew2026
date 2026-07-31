<?php
class ControllerSaleSmsa extends Controller {
    public function index(){
        $json = array();

        $make_xml = $this->createXml($this->request->post['order_id']);

        $response = $this->sendCurl($make_xml);

        $json['post']= $this->request->post;
        $json['xml']=$make_xml;
        $json['returns'] = $response;
        $json['result'] = false;


        $xml = new SimpleXMLElement($response['result']);

        $xmlToArray = $xml->xpath('//soap:Body')[0];

        if($response['error']){
            $json['error'] ='عفواً يوجد خطأ الرجاء المحاولة لاحقاً';
        }else{
            $pos = strpos($xmlToArray->addShipResponse->addShipResult, 'Duplicate Shipment Information');
            if($pos){
                $json['error'] ='الطلب تم ارسالة مسبقاً';
            }else{
                $return = $xmlToArray->addShipResponse->addShipResult;
                $has_error = strpos($return, 'Failed');
                if(preg_match('/Failed/',$return[0])){
                    $json['error'] =$xmlToArray->addShipResponse->addShipResult;
                }else{
                    $json['result'] = $xmlToArray->addShipResponse->addShipResult;
                    $json['addSmsaDb'] =  $this->addSmsaDb($this->request->post['order_id'],$xmlToArray->addShipResponse->addShipResult);

                }
            }
        }


        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function sendCurl($xml){
        $soapUrl = "http://track.smsaexpress.com/SeCom/SMSAwebService.asmx?op=addShip";
        $xml_post_string = $xml;
        $headers = array(
            "POST /SeCom/SMSAwebService.asmx HTTP/1.1",
            "Host: track.smsaexpress.com",
            "Content-Type: text/xml;  charset=utf-8",
            "Content-Length: ".strlen($xml_post_string),
            "SOAPAction: http://track.smsaexpress.com/secom/addShip",
        );

        $url = $soapUrl;
        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL,            $url );
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($soap_do, CURLOPT_POST,           true );
        curl_setopt($soap_do, CURLOPT_POSTFIELDS,    $xml_post_string);
        curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $headers);

        $result = curl_exec($soap_do);
        $err = curl_error($soap_do);

        return array(
            'result'=> $result,
            'error'=>$err,
        );

    }


    public function createXml($id){
        $this->load->model('sale/order');
        $this->load->model('sale/smsa');
        $this->load->model('localisation/zone');
        $this->load->model('customer/custom_field');
        $current_data = date('Y-m-d');
        $ship= '';

        $order_info = $this->model_sale_order->getOrder($id);



        if($order_info){
            $p_name = '';
            $custom_fields = $this->model_customer_custom_field->getCustomFields();

            $products = $this->model_sale_order->getOrderProducts($order_info['order_id']);

            $count_p = count($products);
            $x= 0;
            $count = 0;
            foreach ($products as $product) {
                $x++;
                $count+=(int)$product['quantity'];
                $sep2 = ($count_p !=$x)?' / ':'';
                $p_name .=$product['name'].' - QTY:'.$product['quantity'].$sep2;
            }


            $shipping_custom_fields = array();
            //$city = $this->model_sale_smsa->getCityByName($order_info['shipping_zone']);
            //$city = (!empty($order_info['shipping_city']))?$order_info['shipping_city']:$order_info['shipping_zone'];
            $zone = $this->model_localisation_zone->getZone($order_info['shipping_zone_id']);
            $city = $zone['code'];

            foreach ($custom_fields as $custom_field) {
                if ($custom_field['location'] == 'address' && isset($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
                    if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
                        $custom_field_value_info = $this->model_sale_custom_field->getCustomFieldValue($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

                        if ($custom_field_value_info) {
                            $shipping_custom_fields[] = array(
                                'name'  => $custom_field['name'],
                                'value' => $custom_field_value_info['name'],
                                'sort_order' => $custom_field['sort_order']
                            );
                        }
                    }

                    if ($custom_field['type'] == 'checkbox' && is_array($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
                        foreach ($order_info['shipping_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
                            $custom_field_value_info = $this->model_sale_custom_field->getCustomFieldValue($custom_field_value_id);

                            if ($custom_field_value_info) {
                                $shipping_custom_fields[] = array(
                                    'name'  => $custom_field['name'],
                                    'value' => $custom_field_value_info['name'],
                                    'sort_order' => $custom_field['sort_order']
                                );
                            }
                        }
                    }

                    if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
                        $shipping_custom_fields[] = array(
                            'name'  => $custom_field['name'],
                            'value' => $order_info['shipping_custom_field'][$custom_field['custom_field_id']],
                            'sort_order' => $custom_field['sort_order']
                        );
                    }

                    if ($custom_field['type'] == 'file') {
                        $upload_info = $this->model_tool_upload->getUploadByCode($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

                        if ($upload_info) {
                            $shipping_custom_fields[] = array(
                                'name'  => $custom_field['name'],
                                'value' => $upload_info['name'],
                                'sort_order' => $custom_field['sort_order']
                            );
                        }
                    }
                }
            }

            foreach ($shipping_custom_fields as $custom_field) {
                $sep =($custom_field['value']==count($order_info['shipping_custom_field']))?' , ':' ';
                $ship .= $custom_field['name'].'-'.$custom_field['value'].$sep;
            }
            //$price  = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']);
            $price  = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']);
            $code = ($order_info['payment_code']=='cod')?$order_info['total']:'0';

            $myXMLData = "<?xml version='1.0' encoding='UTF-8'?>";
            $myXMLData .= "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:sec=\"http://track.smsaexpress.com/secom/\">";
            $myXMLData .= "<soapenv:Header/>";
            $myXMLData .= "<soapenv:Body>";
            $myXMLData .= "<sec:addShip>";


            $myXMLData .= "<sec:passKey>Testing1</sec:passKey>";
            $myXMLData .= "<sec:refNo>{$order_info['order_id']}</sec:refNo>";
            $myXMLData .= "<sec:sentDate>{$current_data}</sec:sentDate>";
            $myXMLData .= "<sec:idNo>0</sec:idNo>";
            $myXMLData .= "<sec:cName>{$order_info['firstname']}</sec:cName>";
            $myXMLData .= "<sec:cntry>KSA</sec:cntry>";
            $myXMLData .= "<sec:cCity>{$city}</sec:cCity>";
            $myXMLData .= "<sec:cZip> </sec:cZip>";
            $myXMLData .= "<sec:cPOBox> </sec:cPOBox>";
            $myXMLData .= "<sec:cMobile>{$order_info['telephone']}</sec:cMobile>";
            $myXMLData .= "<sec:cTel1>{$order_info['telephone']}</sec:cTel1>";
            $myXMLData .= "<sec:cTel2> </sec:cTel2>";
            $myXMLData .= "<sec:cAddr1>{$ship}</sec:cAddr1>";
            $myXMLData .= "<sec:cAddr2> </sec:cAddr2>";
            $myXMLData .= "<sec:shipType>DLV</sec:shipType>";
            $myXMLData .= "<sec:PCs>{$count}</sec:PCs>";
            $myXMLData .= "<sec:cEmail>{$order_info['email']}</sec:cEmail>";
            $myXMLData .= "<sec:carrValue>0</sec:carrValue>";
            $myXMLData .= "<sec:carrCurr>SAR</sec:carrCurr>";
            $myXMLData .= "<sec:codAmt>{$code}</sec:codAmt>";
            $myXMLData .= "<sec:weight>1</sec:weight>";
            $myXMLData .= "<sec:custVal>0</sec:custVal>";
            $myXMLData .= "<sec:custCurr>SAR</sec:custCurr>";
            $myXMLData .= "<sec:insrAmt>0</sec:insrAmt>";
            $myXMLData .= "<sec:insrCurr>SAR</sec:insrCurr>";
            $myXMLData .= "<sec:itemDesc>{$p_name}</sec:itemDesc>";
            $myXMLData .= "<sec:sName>www.riadiat.sa</sec:sName>";
            $myXMLData .= "<sec:sContact>Head Office</sec:sContact>";
            $myXMLData .= "<sec:sAddr1>حي النزهة</sec:sAddr1>";
            $myXMLData .= "<sec:sAddr2></sec:sAddr2>";
            $myXMLData .= "<sec:sCity>Ar Riyadh</sec:sCity>";
            $myXMLData .= "<sec:sPhone>0505456384</sec:sPhone>";
            $myXMLData .= "<sec:sCntry>KSA</sec:sCntry>";
            $myXMLData .= "<sec:prefDelvDate> </sec:prefDelvDate>";
            $myXMLData .= "<sec:gpsPoints>0</sec:gpsPoints>";


            $myXMLData .= "</sec:addShip>";
            $myXMLData .= "</soapenv:Body>";
            $myXMLData .= "</soapenv:Envelope>";
            $return = $myXMLData;
        }else{
            $return= false;
        }

        return $return;
    }

    private function addSmsaDb($order_id,$result){
        $this->load->model('sale/smsa');

        return $this->model_sale_smsa->addSmsa($order_id,$result);
    }

}
