<?php
namespace App\Models;

use Core\Model as CoreModel;
use PDO;
use Core\Error;

class Lk extends CoreModel{
    /**Получить страницы меню для шапки
     * @return array
     */
    public function getHeaderPages(){
        $error = '';
        $pages = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT 
                                          url, 
                                          IF(title_menu = '', title, title_menu) as title_menu
                                         FROM pages
                                         WHERE id IN (10, 11, 12, 13)
                                         ORDER BY rate DESC");
            $res->execute();
            while($page = $res->fetch(PDO::FETCH_ASSOC)){
                $page_info = $this->getPageInfo($page['url']);
                $page['full_url'] = $page_info['page']['full_url'];
                array_push($pages, $page);
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['pages'] = $pages;
        return $result;
    }

    /**
     * Типы связи
     * @return array
     */
    public function getConnectTypes(){
        $connect_types = array();
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM ref_connect_types");
            $res->execute();
            $connect_types = $res->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['connect_types'] = $connect_types;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Сохранить основную инфу лк
     * @param $info
     * @return array
     */
    public function saveLkPersonal($info){
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("  UPDATE site_users
                                            SET
                                              fio = :fio,
                                              city = :city,
                                              vuz = :vuz,
                                              faculty = :faculty,
                                              specialty = :specialty,
                                              course = :course
                                            WHERE id = :id");
            $res->bindValue(":id", $_SESSION['user']['id'], PDO::PARAM_INT);
            $res->bindValue(":fio", $info['fio'], PDO::PARAM_STR);
            $res->bindValue(":city", $info['city'], PDO::PARAM_STR);
            $res->bindValue(":vuz", $info['vuz'], PDO::PARAM_STR);
            $res->bindValue(":faculty", $info['faculty'], PDO::PARAM_STR);
            $res->bindValue(":specialty", $info['specialty'], PDO::PARAM_STR);
            $res->bindValue(":course", $info['course'], PDO::PARAM_STR);
            $res->execute();
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['result'] = "Сохранено";
        return $result;
    }

    /**
     * Сохранить контакты лк
     * @param $info
     * @return array
     */
    public function saveLkContacts($info){
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("  UPDATE site_users
                                            SET
                                              phone = :phone,
                                              subscribe = :subscribe
                                            WHERE id = :id");
            $res->bindValue(":id", $_SESSION['user']['id'], PDO::PARAM_INT);
            $res->bindValue(":phone", $info['phone'], PDO::PARAM_STR);
            $res->bindValue(":subscribe", $info['subscribe'], PDO::PARAM_INT);
            $res->execute();
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка обновления данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['result'] = "Сохранено";
        return $result;
    }

    /**
     * Обновить адресс
     * @param $data
     * @return array
     */
    public function updateAddress($data){
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("UPDATE site_user_addresses 
                                          SET
                                            city = :city,
                                            street = :street,
                                            house = :house,
                                            corpus = :corpus,
                                            building = :building,
                                            flat = :flat,
                                            entrance = :entrance,
                                            floor = :floor
                                          WHERE id = :id");
            $res->bindValue(":city", $data['city'], PDO::PARAM_STR);
            $res->bindValue(":street", $data['street'], PDO::PARAM_STR);
            $res->bindValue(":house", $data['house'], PDO::PARAM_INT);
            $res->bindValue(":corpus", $data['corpus'], PDO::PARAM_INT);
            $res->bindValue(":building", $data['building'], PDO::PARAM_INT);
            $res->bindValue(":flat", $data['flat'], PDO::PARAM_INT);
            $res->bindValue(":entrance", $data['entrance'], PDO::PARAM_INT);
            $res->bindValue(":floor", $data['floor'], PDO::PARAM_INT);
            $res->bindValue(":id", $data['id'], PDO::PARAM_INT);
            $res->execute();
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['data'] = $data;
        return $result;
    }

    /**
     * Удалить адресс
     * @param $id
     * @return array
     */
    public function deleteAddress($id){
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("DELETE FROM site_user_addresses
                                          WHERE 
                                            id = :id
                                            AND user_id = :user_id");
            $res->bindValue(":id", $id, PDO::PARAM_INT);
            $res->bindValue(":user_id", $_SESSION['user']['id'], PDO::PARAM_INT);
            $res->execute();
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка удаления данных';
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Заказы пользователя
     * @param int $status
     * @return array
     */
    public function getSiteUserOrders($status = 0){
        $orders = array();
        $error = '';
        try{
            $db = static::getDB();

            $params = array();
            $params[":uid"] = $_SESSION['user']['id'];
            $where = '';
            if ($status > 0) {
                $where = ' AND o.status = :status';
                $params[":status"] = $status;
            }

            $res = $db->prepare("  SELECT 
                                              o.*, 
                                              s.title as work_type_title, 
                                              rs.name as status_name,
                                              rs.bgcolor as status_bgcolor,
                                              ROUND(
                                                (SELECT IFNULL(SUM(op.amount), 0) FROM order_payments op WHERE op.order_id = o.id), 2
                                              ) as payment_sum     
                                            FROM orders o
                                            LEFT JOIN services s ON s.id = o.work_type
                                            LEFT JOIN ref_statuses rs ON rs.id = o.status
                                            WHERE uid = :uid $where
                                            ORDER BY o.id DESC");
            $res->execute($params);
            $orders = $res->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['orders'] = $orders;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Смена пароля
     * @param $old_password
     * @param $new_password
     * @return array
     */
    public function changeSiteUserPassword($old_password, $new_password){
        $error = '';
        $user = $this->getSiteUser($_SESSION['user']['id']);
        if ($user['error']) {
            $error = $user['error'];
        }
        else {
            $user = $user['user'];
            $old_password = md5(md5($old_password).$user['salt']);
            if ($old_password != $user['password']){
                $error = "Указан неверный старый пароль";
            }
            else {
                try {
                    $new_password = md5(md5($new_password).$user['salt']);
                    $db = static::getDB();
                    $res = $db->prepare("UPDATE site_users 
                                                  SET password = :password 
                                                  WHERE id = :id");
                    $res->bindParam(':password', $new_password, PDO::PARAM_STR);
                    $res->bindParam(':id', $_SESSION['user']['id'], PDO::PARAM_INT);
                    $res->execute();
                }
                catch (\PDOException $e){
                    Error::logError($e);
                    $error = "Ошибка обновления пароля";
                }
            }
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Смена пароля при восстановлении доступа
     * @param $user_id
     * @param $new_password
     * @return array
     */
    public function setSiteUserPassword($user_id, $new_password){
        $error = '';
        $user = $this->getSiteUser($user_id);
        if ($user['error']) {
            $error = $user['error'];
        }
        else {
            $user = $user['user'];
            try {
                $new_password = md5(md5($new_password).$user['salt']);
                $db = static::getDB();
                $res = $db->prepare("UPDATE site_users 
                                              SET password = :password 
                                              WHERE id = :id");
                $res->bindParam(':password', $new_password, PDO::PARAM_STR);
                $res->bindParam(':id', $user_id, PDO::PARAM_INT);
                $res->execute();
            }
            catch (\PDOException $e){
                Error::logError($e);
                $error = "Ошибка обновления пароля";
            }
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Данные для оплаты
     * @param $order_id
     * @return array
     */
    public function getOrderPayment($order_id){
        $error = '';
        $sum50 = 0;
        $sum100 = 0;
        $sum_last = 0;
        try{
            $db = static::getDB();
            $res = $db->prepare("SELECT 
                                            o.total_cost,
                                            (SELECT IFNULL(SUM(op.amount), 0) FROM order_payments op WHERE op.order_id = o.id) as payment_sum,
                                            o.work_type
                                          FROM orders o
                                          WHERE o.id = :order_id");
            $res->bindValue(":order_id", $order_id, PDO::PARAM_INT);
            $res->execute();
            $order_info = $res->fetch(PDO::FETCH_ASSOC);
            $sum50 = round($order_info['total_cost'] / 2, 2);
            $sum100 = $order_info['total_cost'];

            if ($order_info['work_type'] == 15){
                $sum50 = 0;
                $sum100 = round($order_info['total_cost'] - $order_info['payment_sum'], 2);
                $sum_last = 0;
            }

            if ($order_info['payment_sum'] > 0) {
                $sum50 = 0;
                $sum100 = 0;
                $sum_last = round($order_info['total_cost'] - $order_info['payment_sum'], 2);
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['sum50'] = $sum50;
        $result['sum100'] = $sum100;
        $result['sum_last'] = $sum_last;
        $result['error'] = $error;
        return $result;
    }

    /**
     * Добавить информацию о платеже
     * @param array $data
     * @return array
     */
    public function addOrderPayment($data){
        $error = "";
        try {
            $time = strtotime($data['datetime']);
            $order_id = (int)$data['label'];
            $notification_type = isset($data['notification_type']) ? $data['notification_type'] : '';
            $amount = isset($data['amount']) ? $data['amount'] : '';
            $sender = isset($data['sender']) ? $data['sender'] : '';
            $operation_label = isset($data['operation_label']) ? $data['operation_label'] : '';
            $operation_id = isset($data['operation_id']) ? $data['operation_id'] : '';
            $email = isset($data['email']) ? $data['email'] : '';
            $lastname = isset($data['lastname']) ? $data['lastname'] : '';
            $firstname = isset($data['firstname']) ? $data['firstname'] : '';
            $fathersname = isset($data['fathersname']) ? $data['fathersname'] : '';

            $db = self::getDB();
            $query = "INSERT INTO `order_payments`
                      SET										
                        `order_id` = :order_id,
                        `time` = :time,
                        `notification_type` = :notification_type,
                        `amount` = :amount,
                        `sender` = :sender,
                        `operation_label` = :operation_label,
                        `operation_id` = :operation_id,
                        `email` = :email,
                        `lastname` = :lastname,
                        `firstname` = :firstname,
                        `fathersname` = :fathersname";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':order_id', $order_id);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':notification_type', $notification_type);
            $stmt->bindValue(':amount', $amount);
            $stmt->bindValue(':sender', $sender);
            $stmt->bindValue(':operation_label', $operation_label);
            $stmt->bindValue(':operation_id', $operation_id);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':lastname', $lastname);
            $stmt->bindValue(':firstname', $firstname);
            $stmt->bindValue(':fathersname', $fathersname);
            $stmt->execute();
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка добавления информации о платеже';
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }
}