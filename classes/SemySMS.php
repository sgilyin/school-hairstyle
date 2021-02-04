<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SemySMS
 *
 * @author sgilyin
 */
class SemySMS {
    public static function trap($inputRequestData, $logDir) {
            if ($inputRequestData['type']=='2' && $inputRequestData['dir']=='in') {
                $phone = substr(preg_replace('/[^0-9]/', '', $inputRequestData['phone']), -15);
                try {
                    $email = DB::query("SELECT email FROM gc_users WHERE phone='$phone'")->fetch_object()->email;
                } catch (Exception $exc) {
                }
    //            $email = DB::query("SELECT email FROM gc_users WHERE phone='$phone'")->fetch_object()->email;
            if (!$email){
                /*
                try {
                    $nameFromWhatsapp = $inputRequestData['messages'][0]['authorName'] ?? $inputRequestData['messages'][0]['nameInMessenger'];
                    $params = Dadata::cleanNameFromWhatsapp($nameFromWhatsapp, $logDir);
                } catch (Exception $exc) {
                }
                */
                preg_match("/\|.*\|/",$inputRequestData['msg'],$matches);
                if ($matches){
                    $item=explode("|", $matches[0]);
                    if ($item[1]){
                        global $addFields;
                        $params['user']['group_name']= array($addFields->{$item[1]});
                    }
                    if ($item[2]){
                        $params['user']['addfields']['d_utm_source']=$item[2];
                    }
                    if ($item[3]){
                        $params['user']['addfields']['d_utm_medium']=$item[3];
                    }
                    if ($item[4]){
                        $params['user']['addfields']['d_utm_content']=$item[4];
                    }
                    if ($item[5]){
                        $params['user']['addfields']['d_utm_campaign']=$item[5];
                    }
                    if ($item[6]){
                        $params['user']['addfields']['d_utm_term']=$item[6];
                    }
                    if ($item[7]){
                        $params['user']['addfields']['d_utm_rs']=$item[7];
                    }
                    if ($item[8]){
                        $params['user']['addfields']['d_utm_acc']=$item[8];
                    }
                    if ($item[9]){
                        $params['user']['addfields']['Возраст']=$item[9];
                    }
                    if ($item[10]){
                        $emailInMessage=$item[10];
                    }
                }
                $email = $emailInMessage ?? "$phone@facebook.com";
            }
            $params['user']['phone'] = $phone;
            $params['user']['email'] = $email;
            $params['user']['addfields']['whatsapp']=$phone;
            GetCourse::addUser($params, $logDir);
            GetCourse::sendContactForm($email, $inputRequestData['msg'].PHP_EOL.'Отправлено из WhatsApp', $logDir);
            DB::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='getcourse'");
        }
    }
}
