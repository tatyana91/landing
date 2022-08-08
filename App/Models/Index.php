<?php
namespace App\Models;

use Core\Model as CoreModel;
use PDO;
use Core\Error;

class Index extends CoreModel{
    /**
     * Информация о нас для главной страницы
     * @return array
     */
    public function getAboutInfo(){
        $error = '';
        $page = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM pages WHERE id = 1");
            $res->execute();
            $page = $res->fetch(PDO::FETCH_ASSOC);

            $page['full_url'] = $page['url'];
            if ($page['parent_id']) {
                $page_link_info = $this->getPageLinkInfo($page['parent_id']);
                $page['full_url'] = $page_link_info['url']."/".$page['url'];
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['page'] = $page;
        return $result;
    }
		
	/**
     * Сео текст для главной страницы
     * @return array
     */
    public function getSeoText(){
        $error = '';
        $page = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM pages WHERE id = 23");
            $res->execute();
            $page = $res->fetch(PDO::FETCH_ASSOC);

            $page['full_url'] = $page['url'];
            if ($page['parent_id']) {
                $page_link_info = $this->getPageLinkInfo($page['parent_id']);
                $page['full_url'] = $page_link_info['url']."/".$page['url'];
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка получения данных';
        }

        $result = array();
        $result['error'] = $error;
        $result['page'] = $page;
        return $result;
    }

    /**
     * Получить гарантии
     * @return array
     */
    public function getPromises(){
        $error = '';
        $pages = array();
        try{
            $db = static::getDB();
            $res = $db->query("SELECT * FROM pages WHERE parent_id = 4 AND archived = '0'");
            $res->execute();
            while ($page = $res->fetch(PDO::FETCH_ASSOC)) {
                $page['full_url'] = $page['url'];
                if ($page['parent_id']) {
                    $page_link_info = $this->getPageLinkInfo($page['parent_id']);
                    $page['full_url'] = $page_link_info['url']."/".$page['url'];
                }
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
     * Добавить сообщение
     * @param array $data
     * @return array
     */
    public function addSiteMessage($data){
        $error = '';
        $confirm_hash = '';
        try{
            $db = static::getDB();

            $time = time();
            $res = $db->prepare("INSERT INTO site_messages 
                                          SET 
                                            fio = :fio, 
                                            email = :email,                                            
                                            phone = :phone,
                                            text = :text,
                                            time = :time,
                                            uid = :uid");
            $res->bindValue(':fio', $data['fio'], PDO::PARAM_STR);
            $res->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $res->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
            $res->bindValue(':text', $data['text'], PDO::PARAM_STR);
            $res->bindValue(':time', $time, PDO::PARAM_INT);
            $res->bindValue(':uid', $data['uid'], PDO::PARAM_INT);
            $res->execute();

            $subject = "Поступило обращение с сайта {$_SERVER['SERVER_NAME']}";
            $message = "Поступило обращение с сайта {$_SERVER['SERVER_NAME']}:<br>";
            $message .= "ФИО: <strong>{$data['fio']}</strong><br>";
            $message .= "Email: <strong>{$data['email']}</strong><br>";
            //$message .= "Телефон: <strong>{$data['phone']}</strong><br>";
            $message .= "Текст: <strong>{$data['text']}</strong><br>";

            $to = "";
            $get_settings = $this->getSettings();
            if ($get_settings['error']) {
                $error = $get_settings['error'];
            }
            else {
                $settings = $get_settings['settings'];
                $to = $settings['mailorders'];
            }
            if ($to) {
                $send_mail = $this->sendMail($to, $subject, $message);
                if (!$send_mail){
                    $error = "Ошибка отправки письма. Сообщите нам об этом, пожалуйста, на эл. почту";
                }
            }
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка отправки сообщения. Сообщите нам об этом, пожалуйста, на эл. почту';
        }

        $result = array();
        $result['error'] = $error;
        $result['confirm_hash'] = $confirm_hash;
        return $result;
    }

    /**
     * Изменение статуса заказа
     * @param $id
     * @param $status
     * @return array
     */
    public function updateOrderStatus($id, $status){
        $error = '';
        try{
            $db = static::getDB();
            $res = $db->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $res->bindValue(":id", $id, PDO::PARAM_INT);
            $res->bindValue(":status", $status, PDO::PARAM_INT);
            $res->execute();
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка удаления заказа';
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }

    /**
     * Подписаться
     * @param $email
     * @return array
     */
    public function subscribe($email){
        $error = '';
        try{
            $db = static::getDB();
            $time = time();
            $res = $db->prepare("INSERT INTO subscribes 
                                          SET 
                                            email = :email,
                                            time = :time");
            $res->bindValue(":email", $email, PDO::PARAM_STR);
            $res->bindValue(":time", $time, PDO::PARAM_INT);
            $res->execute();
        }
        catch (\PDOException $e){
            Error::logError($e);
            $error = 'Ошибка оформления подписки';
        }

        $result = array();
        $result['error'] = $error;
        return $result;
    }
}