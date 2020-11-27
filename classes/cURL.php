<?php

/*
 * Copyright (C) 2020 Sergey Ilyin <developer@ilyins.ru>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of cURL
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class cURL {

    public static function executeRequest($customRequest, $url, $post, $headers, $userpwd, $logDir) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($post){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if ($headers){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($userpwd){
            curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        }
//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//        curl_setopt($ch, CURLOPT_HEADER, TRUE);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($logDir){Logs::add($logDir,'cURL',"$url | " . serialize($post) . " | " . serialize($result) . " | " . serialize($info));}
        return $result;
    }
}