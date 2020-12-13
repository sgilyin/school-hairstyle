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
 * Class for MySQL DB
 *
 * @author Sergey Ilyin <developer@ilyins.ru>
 */
class DB {

    /**
     * Execute request to DB and return result or error
     * 
     * @param string $query
     * @return object(mysqli_result) or integer
     */
    public static function query($query){
        if (DB_HOST && DB_USER && DB_PASSWORD && DB_NAME) {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $mysqli->set_charset('utf8');
            $errNo = $mysqli->errno;
            $result = $mysqli->query($query);
            $mysqli->close();
            switch (strtok($query," ")){
                case 'INSERT':
                case 'DELETE':
                case 'UPDATE':
                    return $errNo;
                default:
                    return $result;
            }
        }
    }

    /**
     * Add user to MySQL database
     * 
     * @param array $inputRequestData
     * @return integer
     */
    public static function addUser($inputRequestData){
        if ($inputRequestData['id'] && $inputRequestData['email']){
            $phoneNum = (empty($inputRequestData['phone'])) ? '' : substr(preg_replace('/[^0-9]/', '', $inputRequestData['phone']), -15);
            $email = $inputRequestData['email'];
            $id = $inputRequestData['id'];
            
            return static::query("INSERT INTO gc_users (`email`, `phone`, `id`) VALUES ('$email', '$phoneNum', '$id')");
        }
    }

    /**
     * Update user in MySQL database
     * 
     * @param array $inputRequestData
     * @return integer
     */
    public static function updateUser($inputRequestData){
        if ($inputRequestData['id'] && $inputRequestData['email'] && $inputRequestData['phone']){
            $phoneNum = substr(preg_replace('/[^0-9]/', '', $inputRequestData['phone']), -15);
            $email = $inputRequestData['email'];
            $id = $inputRequestData['id'];
            
            return static::query("UPDATE gc_users SET email='$email', phone='$phoneNum' WHERE id='$id'");
        }
    }

    public static function deleteUser($inputRequestData){
        if ($inputRequestData['conditions']){
            if ($inputRequestData['conditions']['phone']) {
                $inputRequestData['conditions']['phone'] = substr(preg_replace('/[^0-9]/', '', $inputRequestData['conditions']['phone']), -15);    
            }
            foreach ($inputRequestData['conditions'] as $key => $value) {
                $conditions[] = $key . "='" . $value . "'";
            }
            $conditionsString = implode(" AND ", $conditions);

            return static::query("DELETE FROM gc_users WHERE $conditionsString LIMIT 1");
        }
    }

    /**
     * Synchronizes GetCourse users with MySQL database
     * 
     * @param string $logDir
     * @return boolean
     */
    public static function syncUsers($logDir){
        $mysqli = static::query("SELECT last FROM request WHERE service='getcourse'");
        $result = $mysqli->fetch_object();
        $last = strtotime($result->last);
        $allCount = 0;
        if (time() - $last > 180){
            $export_ids = static::runExports($logDir);
            //static::query("TRUNCATE TABLE gc_users");
            for ($i=0; $i<count($export_ids); $i++) {
                $json = static::getExportData($export_ids[$i],$logDir);
                //var_dump($json);
                for ($j=0; $j<count($json->info->items); $j++) {
                    $allCount++;
                    $id = $json->info->items[$j][0];
                    $email = $json->info->items[$j][1];
                    $phone = substr(preg_replace('/[^0-9]/', '', $json->info->items[$j][7]), -15);
                    static::query("INSERT INTO gc_users (`id`, `email`, `phone`) VALUES ('$id', '$email', '$phone')");
                }
            }
            static::query("UPDATE request SET last=CURRENT_TIMESTAMP() WHERE service='getcourse'");
            return $allCount;
        } else {
            return false;
        }
    }

    /**
     * Run export users from GetCourse
     * 
     * @param string $logDir
     * @return array
     */
    private function runExports($logDir){
#        $export_ids[] = static::getExportId('active', $logDir);
#        $export_ids[] = static::getExportId('in_base', $logDir);
        $export_ids[] = 1186411;
        $export_ids[] = 1186414;

        return $export_ids;
    }

    /**
     * Get export id
     * 
     * @param string $status
     * @param string $logDir
     * @return integer
     */
    private function getExportId($status, $logDir){
        if (GC_ACCOUNT && GC_API_KEY) {
            $post['key'] = GC_API_KEY;
            $url = "https://".GC_ACCOUNT.".getcourse.ru/pl/api/account/users?status=$status";
            do {
                $response = cURL::executeRequest('POST', $url, $post, false, false, $logDir);
                $json = json_decode($response);
                var_dump($json);
                sleep(60);
            } while (!$json->success);
            return $json->info->export_id;
        }
    }

    /**
     * Get export data
     * 
     * @param integer $export_id
     * @param string $logDir
     * @return json
     */
    private function getExportData($export_id, $logDir){
        if (GC_ACCOUNT && GC_API_KEY) {
            $url = "https://".GC_ACCOUNT.".getcourse.ru/pl/api/account/exports/$export_id";
            $post['key'] = GC_API_KEY;
            do {
                $response = cURL::executeRequest('POST', $url, $post, false, false, $logDir);
                $json = json_decode($response);
                sleep(60);
            } while (!$json->success);
            return $json;
        }
    }

    /**
     * Show send queue to Wazzup24
     * 
     * @return string
     */
    public static function showWa24Queue() {
        return static::query("SELECT COUNT(*) AS count FROM send_to_wazzup24 WHERE success=0")->fetch_object()->count;
    }

    /**
     * Clear queue to Wazzup24
     * 
     * @return string
     */
    public static function clearWa24Queue() {
        static::query("TRUNCATE send_to_wazzup24");
    }

    /**
     * Show send queue to Vkontakte
     * 
     * @return string
     */
    public static function showVkQueue() {
        return static::query("SELECT COUNT(*) AS count FROM vk_api WHERE success=0")->fetch_object()->count;
    }
}